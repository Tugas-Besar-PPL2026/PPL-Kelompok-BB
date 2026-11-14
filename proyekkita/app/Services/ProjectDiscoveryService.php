<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * [PBI-14 / FR-08] Data untuk daftar proyek dan halaman Detail Proyek bagi kandidat.
 * Pencarian, filter, dan pengurutan (PBI-15) belum termasuk di sini.
 */
class ProjectDiscoveryService
{
    /** Status proyek yang boleh dibuka kandidat lewat halaman detail. Draf, moderasi, dan ditolak tidak boleh. */
    public const VIEWABLE = [ProjectStatus::Published, ProjectStatus::Closed];

    /** Daftar proyek yang dipublikasikan dan tenggat lamarannya belum lewat, terbaru lebih dulu. */
    public function listOpen(int $perPage = 10): LengthAwarePaginator
    {
        return Project::query()
            ->openForApplications()
            ->with(['category', 'skills', 'umkmProfile'])
            ->withCount([
                'paymentTerms',
                'applications as accepted_count' => fn ($q) => $q->where('status', ApplicationStatus::Accepted),
            ])
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
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
