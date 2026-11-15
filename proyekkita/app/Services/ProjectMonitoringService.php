<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectModeration;
use App\Models\UmkmProfile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-13 / FR-06, FR-07] Halaman Proyek Saya untuk UMKM: daftar proyek milik sendiri, status,
 * hasil moderasi terakhir, dan aksi yang relevan untuk tiap status.
 * Halaman ini hanya membaca data. Membuat/mengubah draf ada di ProjectService (PBI-09),
 * pengajuan moderasi di PBI-11, dan keputusan moderasi di PBI-12.
 */
class ProjectMonitoringService
{
    /** Urutan tab status di halaman Proyek Saya. */
    public const TABS = [
        ProjectStatus::Published,
        ProjectStatus::PendingReview,
        ProjectStatus::RevisionRequested,
        ProjectStatus::Draft,
        ProjectStatus::Rejected,
        ProjectStatus::Closed,
    ];

    /** Proyek milik satu UMKM, terbaru dulu. $status = null berarti semua status. */
    public function projects(UmkmProfile $umkm, ?ProjectStatus $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Project::query()
            ->where('umkm_profile_id', $umkm->id)
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->with(['category', 'moderations'])
            ->withCount([
                // Lamaran yang dibatalkan kandidat tidak dihitung sebagai pelamar.
                'applications as applicants_count' => fn ($query) => $query->where('status', '!=', ApplicationStatus::Cancelled->value),
            ])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Jumlah proyek per status (semua status selalu ada, minimal 0) ditambah kunci 'all'.
     *
     * @return array<string, int>
     */
    public function statusCounts(UmkmProfile $umkm): array
    {
        $rows = DB::table('projects')
            ->where('umkm_profile_id', $umkm->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => 0];
        foreach (ProjectStatus::cases() as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
            $counts['all'] += $counts[$status->value];
        }

        return $counts;
    }

    /** Total pelamar di semua proyek milik UMKM ini (lamaran dibatalkan tidak dihitung). */
    public function totalApplicants(UmkmProfile $umkm): int
    {
        return Application::query()
            ->whereIn('project_id', Project::query()->where('umkm_profile_id', $umkm->id)->select('id'))
            ->where('status', '!=', ApplicationStatus::Cancelled->value)
            ->count();
    }

    /** Hasil moderasi paling baru untuk proyek ini, atau null kalau belum pernah dimoderasi. */
    public function latestModeration(Project $project): ?ProjectModeration
    {
        return $project->moderations->sortByDesc('id')->first();
    }

    /**
     * Aksi utama pada kartu proyek, tergantung status. Null berarti tidak ada aksi (sedang diperiksa / ditolak).
     *
     * @return array{label: string, url: string, dusk: string, style: string}|null
     */
    public function actionFor(Project $project): ?array
    {
        return match ($project->status) {
            ProjectStatus::Draft => [
                'label' => 'Lanjutkan Draf',
                'url' => route('umkm.proyek.edit', $project),
                'dusk' => "lanjutkan-draf-{$project->id}",
                'style' => 'btn o sm',
            ],
            ProjectStatus::RevisionRequested => [
                'label' => 'Perbaiki & Ajukan Ulang',
                'url' => route('umkm.proyek.edit', $project),
                'dusk' => "perbaiki-{$project->id}",
                'style' => 'btn p sm',
            ],
            ProjectStatus::Published, ProjectStatus::Closed => [
                'label' => 'Lihat Pelamar',
                'url' => route('umkm.seleksi.index', ['proyek' => $project->id]),
                'dusk' => "lihat-pelamar-proyek-{$project->id}",
                'style' => 'btn p sm',
            ],
            default => null,
        };
    }
}
