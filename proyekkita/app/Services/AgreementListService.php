<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Models\User;
use App\Support\AgreementPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * [PBI-26 / FR-12] Daftar, detail, dan status kesepakatan untuk UMKM dan Kandidat.
 *
 * Data selalu dibatasi pada pemiliknya:
 *  - UMKM     : kesepakatan pada proyek milik profil usahanya (termasuk Draf yang belum dikirim)
 *  - Kandidat : kesepakatan atas lamarannya sendiri, TANPA Draf (draf belum dikirim UMKM)
 * Jumlah pada setiap tab dihitung dari database (badge dinamis). Halaman ini hanya membaca data.
 */
class AgreementListService
{
    public const PER_PAGE = 10;

    /** Tab UMKM: kunci => [label, status yang termasuk]. */
    public const UMKM_TABS = [
        'draft' => ['Draf', [AgreementStatus::Draft]],
        'sent' => ['Dikirim', [AgreementStatus::Sent]],
        'active' => ['Aktif', [AgreementStatus::Active]],
        'completed' => ['Selesai', [AgreementStatus::Completed]],
        'closed' => ['Ditolak/Dibatalkan', [AgreementStatus::Rejected, AgreementStatus::Cancelled]],
    ];

    /** Tab Kandidat (mockup 4.5.24). Draf tidak pernah ditampilkan kepada kandidat. */
    public const CANDIDATE_TABS = [
        'active' => ['Aktif', [AgreementStatus::Active]],
        'sent' => ['Perlu Tanggapan', [AgreementStatus::Sent]],
        'completed' => ['Selesai', [AgreementStatus::Completed]],
        'closed' => ['Ditolak/Dibatalkan', [AgreementStatus::Rejected, AgreementStatus::Cancelled]],
    ];

    public const DEFAULT_TAB = 'active';

    public function umkmQuery(User $user): Builder
    {
        $profileId = $user->umkmProfile?->id ?? 0;

        return Agreement::query()->whereHas(
            'application.project',
            fn (Builder $q) => $q->where('umkm_profile_id', $profileId)
        );
    }

    public function candidateQuery(User $user): Builder
    {
        $profileId = $user->candidateProfile?->id ?? 0;

        return Agreement::query()
            ->whereHas('application', fn (Builder $q) => $q->where('candidate_profile_id', $profileId))
            ->where('status', '!=', AgreementStatus::Draft->value);
    }

    /** Kunci tab dari ?status=..., nilai tidak dikenal kembali ke tab Aktif. */
    public function tabFromRequest(?string $value, array $tabs): string
    {
        return array_key_exists((string) $value, $tabs) ? (string) $value : self::DEFAULT_TAB;
    }

    /** Tab tempat sebuah kesepakatan berada. */
    public function tabFor(Agreement $agreement, array $tabs): string
    {
        foreach ($tabs as $key => [, $statuses]) {
            if (in_array($agreement->status, $statuses, true)) {
                return $key;
            }
        }

        return self::DEFAULT_TAB;
    }

    /** @return array<string, int> jumlah kesepakatan per tab */
    public function counts(Builder $query, array $tabs): array
    {
        $perStatus = (clone $query)
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];
        foreach ($tabs as $key => [, $statuses]) {
            $counts[$key] = (int) collect($statuses)->sum(fn (AgreementStatus $s) => (int) ($perStatus[$s->value] ?? 0));
        }

        return $counts;
    }

    public function list(Builder $query, array $statuses): LengthAwarePaginator
    {
        return (clone $query)
            ->whereIn('status', array_map(fn (AgreementStatus $s) => $s->value, $statuses))
            ->with([
                'application.candidateProfile',
                'application.project.umkmProfile',
                'progressUpdates',
            ])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /** Data detail kesepakatan yang dipilih (bagian bawah halaman Kesepakatan & Progres). */
    public function detail(Agreement $agreement): array
    {
        $agreement->load([
            'application.candidateProfile',
            'application.project.umkmProfile',
            'application.project.paymentTerms',
        ]);

        $project = $agreement->application->project;
        $showsUsage = in_array($agreement->status, [AgreementStatus::Active, AgreementStatus::Completed], true);

        return [
            'agreement' => $agreement,
            'project' => $project,
            'candidate' => $agreement->application->candidateProfile,
            'umkm' => $project->umkmProfile,
            'code' => AgreementPresenter::code($agreement),
            'deadline' => AgreementPresenter::deadline($agreement->deadline),
            'stages' => AgreementPresenter::stages($agreement),
            'terms' => AgreementPresenter::terms($project),
            'deliverables' => AgreementPresenter::deliverables($agreement->deliverables),
            // Revisi terpakai dibaca dari PBI-32 (revision_requests) hanya untuk ditampilkan.
            'revisionsUsed' => $showsUsage ? $agreement->revisionRequests()->count() : null,
        ];
    }
}
