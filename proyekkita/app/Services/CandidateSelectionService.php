<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Evaluation;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Support\SelectionPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Service Candidate Selection (FR-11), bagian PBI-19: membaca daftar pelamar dan detail kandidat untuk UMKM.
 * Hanya membaca data. Perubahan status lamaran (PBI-20 dan PBI-21) tidak ditangani di sini.
 */
class CandidateSelectionService
{
    /**
     * Status yang tampil pada daftar pelamar. Lamaran Dibatalkan (dibatalkan sendiri oleh kandidat) tidak punya
     * tab maupun badge pada mockup, sehingga tidak dihitung sebagai pelamar aktif.
     *
     * @var list<ApplicationStatus>
     */
    public const VISIBLE = [
        ApplicationStatus::Pending,
        ApplicationStatus::Shortlisted,
        ApplicationStatus::Accepted,
        ApplicationStatus::Rejected,
    ];

    public const PER_PAGE = 15;

    /** Disk tempat berkas portofolio dicari, berurutan (disk default aplikasi lalu disk public). */
    private const FILE_DISKS = ['local', 'public'];

    /**
     * Proyek milik UMKM untuk dropdown: proyek yang sudah bisa/pernah menerima lamaran (dipublikasikan atau ditutup),
     * ditambah proyek apa pun yang sudah punya lamaran. Yang terbaru lebih dulu.
     *
     * @return Collection<int, Project>
     */
    public function projectsFor(UmkmProfile $umkm): Collection
    {
        return Project::query()
            ->where('umkm_profile_id', $umkm->id)
            ->where(function ($query) {
                $query->whereIn('status', [ProjectStatus::Published->value, ProjectStatus::Closed->value])
                    ->orWhereHas('applications');
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get(['id', 'umkm_profile_id', 'title', 'status', 'quota', 'published_at']);
    }

    /**
     * Jumlah lamaran per status (tanpa yang Dibatalkan) dalam satu query.
     *
     * @return array{all: int, pending: int, shortlisted: int, accepted: int, rejected: int}
     */
    public function counts(Project $project): array
    {
        $byStatus = $project->applications()
            ->whereIn('status', array_map(fn (ApplicationStatus $s) => $s->value, self::VISIBLE))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];
        foreach (self::VISIBLE as $status) {
            $counts[$status->value] = (int) ($byStatus[$status->value] ?? 0);
        }

        return ['all' => array_sum($counts)] + $counts;
    }

    /**
     * Kuota kandidat: hanya lamaran berstatus Diterima yang mengisi kuota (bukan Terpilih Sementara).
     *
     * @return array{accepted: int, total: int, percent: int, remaining: int, full: bool}
     */
    public function quota(Project $project, ?int $accepted = null): array
    {
        $accepted ??= $project->acceptedCount();
        $total = max(0, (int) $project->quota);
        $percent = $total > 0 ? (int) min(100, round($accepted / $total * 100)) : 0;
        $remaining = max(0, $total - $accepted);

        return [
            'accepted' => $accepted,
            'total' => $total,
            'percent' => $percent,
            'remaining' => $remaining,
            'full' => $remaining === 0,
        ];
    }

    /** Definisi tab status beserta jumlahnya. Kunci kosong berarti "Semua". */
    public function tabs(array $counts): array
    {
        $tabs = [['key' => '', 'label' => 'Semua', 'count' => $counts['all']]];
        foreach (self::VISIBLE as $status) {
            $tabs[] = ['key' => $status->value, 'label' => $status->label(), 'count' => $counts[$status->value]];
        }

        return $tabs;
    }

    /** Status dari parameter query, atau null (Semua) bila kosong/tidak dikenal/di luar tab yang tersedia. */
    public function statusFromQuery(mixed $value): ?ApplicationStatus
    {
        $status = is_string($value) ? ApplicationStatus::tryFrom($value) : null;

        return $status && in_array($status, self::VISIBLE, true) ? $status : null;
    }

    /** Teks "sejak ..." pada kartu Total Pelamar: tanggal publikasi proyek, atau lamaran pertama bila belum ada. */
    public function listedSince(Project $project, int $total): string
    {
        $since = $project->published_at;
        if (! $since && $total > 0) {
            $first = $project->applications()->min('created_at');
            $since = $first ? Carbon::parse($first) : null;
        }

        return $since ? 'sejak '.SelectionPresenter::date($since) : 'belum dipublikasikan';
    }

    /**
     * Daftar pelamar satu proyek, dengan eager loading dan dibatasi per halaman.
     * Urutan: Diterima, Terpilih Sementara, Menunggu Keputusan, Ditolak, lalu yang lebih dulu melamar.
     * Setiap baris sudah memuat ringkasan pengalaman, penilaian, dan jumlah proyek selesai.
     */
    public function applicants(Project $project, ?ApplicationStatus $status): LengthAwarePaginator
    {
        $statuses = $status ? [$status] : self::VISIBLE;

        $paginator = $project->applications()
            ->with(['candidateProfile.skills' => fn ($q) => $q->orderBy('skills.id'), 'candidateProfile.experiences'])
            ->whereIn('status', array_map(fn (ApplicationStatus $s) => $s->value, $statuses))
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 ELSE 5 END',
                [
                    ApplicationStatus::Accepted->value,
                    ApplicationStatus::Shortlisted->value,
                    ApplicationStatus::Pending->value,
                    ApplicationStatus::Rejected->value,
                ],
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $stats = $this->candidateStats($paginator->getCollection()->pluck('candidateProfile')->filter());

        return $paginator->through(function (Application $application) use ($stats) {
            $profile = $application->candidateProfile;

            return [
                'application' => $application,
                'profile' => $profile,
                'skills' => $profile->skills,
                'experience' => SelectionPresenter::experienceSummary($profile->experiences),
                'stats' => $stats[$profile->id],
            ];
        });
    }

    /**
     * Rating rata-rata, jumlah evaluasi, dan jumlah proyek selesai untuk sekumpulan kandidat.
     * Dihitung dengan dua query teragregasi (bukan satu query per kandidat).
     *
     * @param  Collection<int, CandidateProfile>  $profiles
     * @return array<int, array{rating: ?float, evaluations: int, completed: int}> dikunci dengan id candidate_profiles
     */
    public function candidateStats(Collection $profiles): array
    {
        $profiles = $profiles->unique('id');
        if ($profiles->isEmpty()) {
            return [];
        }

        $ratings = Evaluation::query()
            ->whereIn('reviewee_id', $profiles->pluck('user_id'))
            ->selectRaw('reviewee_id, AVG(rating) as average, COUNT(*) as total')
            ->groupBy('reviewee_id')
            ->get()
            ->keyBy('reviewee_id');

        $completed = Agreement::query()
            ->join('applications', 'applications.id', '=', 'agreements.application_id')
            ->whereIn('applications.candidate_profile_id', $profiles->pluck('id'))
            ->where('agreements.status', AgreementStatus::Completed->value)
            ->selectRaw('applications.candidate_profile_id as profile_id, COUNT(*) as total')
            ->groupBy('applications.candidate_profile_id')
            ->pluck('total', 'profile_id');

        return $profiles->mapWithKeys(function (CandidateProfile $profile) use ($ratings, $completed) {
            $rating = $ratings->get($profile->user_id);

            return [$profile->id => [
                'rating' => $rating ? round((float) $rating->average, 1) : null,
                'evaluations' => $rating ? (int) $rating->total : 0,
                'completed' => (int) ($completed[$profile->id] ?? 0),
            ]];
        })->all();
    }

    /**
     * Memuat semua relasi yang dibutuhkan halaman detail dalam satu rangkaian eager loading.
     */
    public function loadDetail(Application $application): Application
    {
        return $application->load([
            'project.skills',
            'candidateProfile.skills',
            'candidateProfile.experiences' => fn ($q) => $q->orderBy('id'),
            'candidateProfile.portfolios' => fn ($q) => $q->orderBy('id'),
            'statusHistories' => fn ($q) => $q->orderByDesc('id'),
        ]);
    }

    /**
     * Keterampilan kandidat, ditandai mana yang dibutuhkan proyek; yang dibutuhkan ditaruh lebih dulu,
     * selebihnya mengikuti urutan data master keterampilan.
     *
     * @return Collection<int, array{id: int, name: string, required: bool}>
     */
    public function skillBadges(Application $application): Collection
    {
        $required = $application->project->skills->pluck('id')->flip();

        return $application->candidateProfile->skills
            ->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name, 'required' => $required->has($skill->id)])
            ->sortBy([['required', 'desc'], ['id', 'asc']])
            ->values();
    }

    /**
     * Bahan tampilan portofolio. Tautan hanya diteruskan bila berskema http/https. Dokumen tidak pernah ditautkan
     * langsung ke storage: dibuka lewat route yang memeriksa kepemilikan proyek.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function portfolioItems(Application $application): Collection
    {
        return $application->candidateProfile->portfolios->map(function (Portfolio $portfolio) {
            $url = SelectionPresenter::safeUrl($portfolio->link);
            $file = $this->locateFile($portfolio);

            return [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'url' => $url,
                'url_label' => $url ? SelectionPresenter::displayUrl($url) : null,
                'has_file' => filled($portfolio->file_path),
                'file_available' => $file !== null,
                'file_name' => filled($portfolio->file_path) ? basename($portfolio->file_path) : null,
                'file_size' => $file ? SelectionPresenter::fileSize($file['size']) : null,
            ];
        });
    }

    /**
     * Mencari berkas portofolio pada disk yang dikenal.
     *
     * @return array{disk: string, path: string, size: int}|null
     */
    public function locateFile(Portfolio $portfolio): ?array
    {
        $path = (string) $portfolio->file_path;
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        foreach (self::FILE_DISKS as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return ['disk' => $disk, 'path' => $path, 'size' => (int) Storage::disk($disk)->size($path)];
            }
        }

        return null;
    }
}
