<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * [PBI-14 + PBI-15 / FR-08] Data untuk daftar proyek, pencarian/filter/pengurutan, dan halaman Detail Proyek
 * bagi kandidat.
 */
class ProjectDiscoveryService
{
    /** Status proyek yang boleh dibuka kandidat lewat halaman detail. Draf, moderasi, dan ditolak tidak boleh. */
    public const VIEWABLE = [ProjectStatus::Published, ProjectStatus::Closed];

    /** [PBI-15] Pilihan pengurutan: nilai query string => label di antarmuka. */
    public const SORTS = [
        'terbaru' => 'Terbaru',
        'tenggat_terdekat' => 'Tenggat lamaran terdekat',
        'kompensasi_tertinggi' => 'Kompensasi tertinggi',
        'kompensasi_terendah' => 'Kompensasi terendah',
    ];

    public const DEFAULT_SORT = 'terbaru';

    /**
     * Daftar proyek yang dipublikasikan dan tenggat lamarannya belum lewat.
     * $filters (lihat ProjectSearchRequest::filters): cari, kategori, keterampilan, kompensasi_min,
     * kompensasi_maks, tenggat_sebelum, urut. Kunci yang tidak ada dianggap tidak dipakai.
     */
    public function listOpen(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Project::query()
            ->openForApplications()
            ->with(['category', 'skills', 'umkmProfile'])
            ->withCount([
                'paymentTerms',
                'applications as accepted_count' => fn ($q) => $q->where('status', ApplicationStatus::Accepted),
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['urut'] ?? self::DEFAULT_SORT);

        return $query->paginate($perPage);
    }

    /** [PBI-15] Pencarian kata kunci (judul atau deskripsi) dan filter. Semua filter digabung dengan AND. */
    private function applyFilters(Builder $query, array $filters): void
    {
        $keyword = trim((string) ($filters['cari'] ?? ''));
        if ($keyword !== '') {
            // Karakter khusus LIKE (% dan _) di-escape agar dicari apa adanya. '!' dipakai sebagai karakter escape.
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw("projects.title LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("projects.description LIKE ? ESCAPE '!'", [$like]);
            });
        }

        if (! empty($filters['kategori'])) {
            $query->where('projects.category_id', (int) $filters['kategori']);
        }

        // Proyek yang membutuhkan SALAH SATU keterampilan terpilih.
        if (! empty($filters['keterampilan'])) {
            $skillIds = array_map('intval', (array) $filters['keterampilan']);
            $query->whereHas('skills', fn (Builder $q) => $q->whereIn('skills.id', $skillIds));
        }

        if (isset($filters['kompensasi_min']) && $filters['kompensasi_min'] !== '') {
            $query->where('projects.total_compensation', '>=', $filters['kompensasi_min']);
        }

        if (isset($filters['kompensasi_maks']) && $filters['kompensasi_maks'] !== '') {
            $query->where('projects.total_compensation', '<=', $filters['kompensasi_maks']);
        }

        // Tenggat lamaran paling lambat pada tanggal ini (tanggal itu sendiri ikut).
        if (! empty($filters['tenggat_sebelum'])) {
            $query->whereDate('projects.application_deadline', '<=', $filters['tenggat_sebelum']);
        }
    }

    /** [PBI-15] Pengurutan. Urutan cadangan selalu id terbaru supaya hasil stabil saat nilai sama. */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'tenggat_terdekat' => $query->orderBy('projects.application_deadline'),
            'kompensasi_tertinggi' => $query->orderByDesc('projects.total_compensation'),
            'kompensasi_terendah' => $query->orderBy('projects.total_compensation'),
            default => $query->orderByDesc('projects.published_at'),
        };

        $query->orderByDesc('projects.id');
    }

    /** Jumlah filter yang sedang aktif (urutan tidak dihitung). */
    public function activeFilterCount(array $filters): int
    {
        $count = 0;
        foreach (['cari', 'kategori', 'keterampilan', 'kompensasi_min', 'kompensasi_maks', 'tenggat_sebelum'] as $key) {
            $value = $filters[$key] ?? null;
            if ($value !== null && $value !== '' && $value !== []) {
                $count++;
            }
        }

        return $count;
    }

    /** Pilihan kategori untuk filter (hanya yang aktif). */
    public function categoryOptions(): Collection
    {
        return Category::active()->orderBy('name')->get();
    }

    /** Pilihan keterampilan untuk filter (hanya yang aktif). */
    public function skillOptions(): Collection
    {
        return Skill::active()->orderBy('name')->get();
    }

    /** Memuat semua relasi yang dibutuhkan halaman detail. */
    public function loadDetail(Project $project): Project
    {
        return $project->load(['category', 'skills', 'paymentTerms', 'umkmProfile.user']);
    }

    /**
     * Hasil pekerjaan yang diharapkan, satu butir per baris. Tanda poin di awal baris dibuang
     * karena tampilan sudah memberi poin sendiri.
     *
     * @return list<string>
     */
    public function expectedOutputItems(Project $project): array
    {
        return collect(preg_split('/\R/u', (string) $project->expected_output))
            ->map(fn ($line) => trim(preg_replace('/^[\s•\-\*]+/u', '', $line)))
            ->filter()
            ->values()
            ->all();
    }

    /** Keterangan sisa waktu menuju tenggat lamaran, misalnya "16 hari lagi". */
    public function deadlineNote(Project $project): string
    {
        $days = (int) Carbon::today()->diffInDays($project->application_deadline->copy()->startOfDay(), false);

        return match (true) {
            $days > 0 => "{$days} hari lagi",
            $days === 0 => 'Hari ini hari terakhir',
            default => 'Berakhir ' . abs($days) . ' hari lalu',
        };
    }

    /** Sisa kuota = kuota dikurangi jumlah kandidat yang sudah diterima. */
    public function remainingQuota(Project $project, ?int $acceptedCount = null): int
    {
        return max(0, $project->quota - ($acceptedCount ?? $project->acceptedCount()));
    }
}
