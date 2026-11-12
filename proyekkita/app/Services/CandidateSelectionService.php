<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Evaluation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Logika bisnis Seleksi Kandidat (FR-11) untuk sisi UMKM: proyek yang bisa dipilih, statistik pelamar,
 * kuota, daftar pelamar, serta reputasi kandidat (rating dan proyek selesai).
 * Service ini hanya membaca data. Perubahan status lamaran (PBI-20 dan PBI-21) tidak dilakukan di sini.
 */
class CandidateSelectionService
{
    /** Status yang menjadi tab di halaman daftar pelamar, sesuai urutan tampilan. */
    public const TABS = [
        ApplicationStatus::Pending,
        ApplicationStatus::Shortlisted,
        ApplicationStatus::Accepted,
        ApplicationStatus::Rejected,
    ];

    /** Proyek milik UMKM yang bisa memiliki pelamar (sudah dipublikasikan atau sudah ditutup). */
    public function projectsFor(User $user): Collection
    {
        $umkmId = $user->umkmProfile?->id;
        if ($umkmId === null) {
            return collect();
        }

        return Project::query()
            ->where('umkm_profile_id', $umkmId)
            ->whereIn('status', [ProjectStatus::Published->value, ProjectStatus::Closed->value])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Jumlah pelamar per status. Lamaran yang dibatalkan kandidat tidak dihitung sebagai pelamar.
     *
     * @return array{all: int, pending: int, shortlisted: int, accepted: int, rejected: int}
     */
    public function statusCounts(Project $project): array
    {
        $rows = Application::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', ApplicationStatus::Cancelled)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);

        $counts = ['all' => (int) $rows->sum()];
        foreach (self::TABS as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        return $counts;
    }

    /** Nama lain untuk statusCounts(); dipakai test PBI-18 (BatalkanLamaranTest). */
    public function counts(Project $project): array
    {
        return $this->statusCounts($project);
    }

    /**
     * Kuota proyek. Yang dihitung terisi hanya lamaran berstatus Diterima, bukan Terpilih Sementara.
     *
     * @return array{accepted: int, total: int, percent: int, remaining: int, full: bool}
     */
    public function quota(Project $project, ?int $accepted = null): array
    {
        // Bila jumlah diterima tidak diberikan, hitung dari database (hanya status Diterima).
        $accepted ??= Application::query()
            ->where('project_id', $project->id)
            ->where('status', ApplicationStatus::Accepted->value)
            ->count();

        $total = max((int) $project->quota, 0);
        $percent = $total > 0 ? (int) min(100, round($accepted / $total * 100)) : 0;

        return [
            'accepted' => $accepted,
            'total' => $total,
            'percent' => $percent,
            'remaining' => max($total - $accepted, 0),
            'full' => $total > 0 && $accepted >= $total,
        ];
    }

    /** Daftar pelamar satu proyek (dengan relasi yang dibutuhkan tabel), dipaginasi dan dapat difilter status. */
    public function applicants(Project $project, ?ApplicationStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return Application::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', ApplicationStatus::Cancelled)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with([
                'candidateProfile.skills' => fn ($query) => $query->orderBy('skills.name'),
                'candidateProfile.experiences',
            ])
            ->orderByRaw("CASE status WHEN 'accepted' THEN 1 WHEN 'shortlisted' THEN 2 WHEN 'pending' THEN 3 WHEN 'rejected' THEN 4 ELSE 5 END")
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * Reputasi kandidat dari evaluasi proyek sebelumnya, diambil dengan dua query untuk semua profil sekaligus.
     *
     * @param  Collection<int, \App\Models\CandidateProfile>  $profiles
     * @return array<int, array{rating: ?float, evaluations: int, completed: int}> dikunci dengan id profil kandidat
     */
    public function reputation(Collection $profiles): array
    {
        $profiles = $profiles->filter();
        if ($profiles->isEmpty()) {
            return [];
        }

        $ratings = Evaluation::query()
            ->whereIn('reviewee_id', $profiles->pluck('user_id')->unique())
            ->selectRaw('reviewee_id, AVG(rating) as avg_rating, COUNT(*) as total')
            ->groupBy('reviewee_id')
            ->get()
            ->keyBy('reviewee_id');

        $completed = Agreement::query()
            ->join('applications', 'applications.id', '=', 'agreements.application_id')
            ->where('agreements.status', AgreementStatus::Completed->value)
            ->whereIn('applications.candidate_profile_id', $profiles->pluck('id'))
            ->selectRaw('applications.candidate_profile_id as profile_id, COUNT(*) as total')
            ->groupBy('applications.candidate_profile_id')
            ->get()
            ->keyBy('profile_id');

        return $profiles->mapWithKeys(function ($profile) use ($ratings, $completed) {
            $rating = $ratings->get($profile->user_id);

            return [$profile->id => [
                'rating' => $rating ? round((float) $rating->avg_rating, 1) : null,
                'evaluations' => $rating ? (int) $rating->total : 0,
                'completed' => (int) ($completed->get($profile->id)?->total ?? 0),
            ]];
        })->all();
    }
}