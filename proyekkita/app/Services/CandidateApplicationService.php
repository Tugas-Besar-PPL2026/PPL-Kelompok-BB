<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * [PBI-17 / FR-10] Memantau status dan riwayat lamaran milik kandidat.
 *
 * Service ini hanya MEMBACA data. Membuat lamaran adalah PBI-16, membatalkan lamaran adalah PBI-18,
 * dan mengubah status lamaran adalah PBI-20/21 (UMKM).
 *
 * Keamanan: semua query selalu dibatasi pada candidate_profile_id milik kandidat yang login (lihat baseQuery),
 * sehingga lamaran kandidat lain tidak mungkin ikut terbaca.
 */
class CandidateApplicationService
{
    public const PER_PAGE = 10;

    /**
     * Urutan kelompok status pada daftar (mengikuti mockup): Diterima, Terpilih Sementara, Menunggu Keputusan,
     * Ditolak, lalu Dibatalkan. Di dalam satu kelompok, lamaran terbaru tampil lebih dulu.
     *
     * @var list<ApplicationStatus>
     */
    public const LIST_ORDER = [
        ApplicationStatus::Accepted,
        ApplicationStatus::Shortlisted,
        ApplicationStatus::Pending,
        ApplicationStatus::Rejected,
        ApplicationStatus::Cancelled,
    ];

    /**
     * Jumlah lamaran per status. Kunci 'all' berisi total. Dihitung dengan satu query teragregasi.
     *
     * @return array{all: int, pending: int, shortlisted: int, accepted: int, rejected: int, cancelled: int}
     */
    public function counts(?CandidateProfile $profile): array
    {
        $byStatus = $this->baseQuery($profile)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];
        foreach (ApplicationStatus::cases() as $status) {
            $counts[$status->value] = (int) ($byStatus[$status->value] ?? 0);
        }

        return ['all' => array_sum($counts)] + $counts;
    }

    /**
     * Definisi tab: Semua lalu setiap status yang ada di enum (urutan sama dengan mockup).
     * Kunci kosong berarti "Semua".
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public function tabs(array $counts): array
    {
        $tabs = [['key' => '', 'label' => 'Semua', 'count' => $counts['all']]];

        foreach (ApplicationStatus::cases() as $status) {
            $tabs[] = ['key' => $status->value, 'label' => $status->label(), 'count' => $counts[$status->value]];
        }

        return $tabs;
    }

    /** Status dari parameter ?status=, atau null (Semua) bila kosong atau tidak dikenal. */
    public function statusFromQuery(mixed $value): ?ApplicationStatus
    {
        return is_string($value) ? ApplicationStatus::tryFrom($value) : null;
    }

    /**
     * Daftar lamaran milik kandidat, siap ditampilkan. Relasi dimuat sekaligus (eager loading)
     * agar jumlah query tidak bertambah seiring jumlah lamaran.
     */
    public function applications(?CandidateProfile $profile, ?ApplicationStatus $status): LengthAwarePaginator
    {
        $paginator = $this->baseQuery($profile)
            ->with([
                'project.umkmProfile',
                // Terbaru lebih dulu. id dipakai sebagai pemecah seri bila changed_at sama atau kosong.
                'statusHistories' => fn ($query) => $query->reorder()->orderByDesc('changed_at')->orderByDesc('id'),
            ])
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 ELSE 5 END',
                array_map(fn (ApplicationStatus $s) => $s->value, array_slice(self::LIST_ORDER, 0, 4)),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return $paginator->through(fn (Application $application) => $this->present($application));
    }

    /**
     * Bahan tampilan satu kartu lamaran. Hanya memakai data yang benar-benar ada di database:
     * tanggal kosong tidak ditampilkan dan catatan kosong tidak dibuat-buat.
     *
     * @return array<string, mixed>
     */
    public function present(Application $application): array
    {
        $histories = $application->statusHistories; // terbaru -> terlama
        $timeline = $this->timeline($histories);

        $isActive = in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Shortlisted], true);
        $cancelled = $histories->first(fn (ApplicationStatusHistory $h) => $h->status === ApplicationStatus::Cancelled);
        $note = trim((string) $application->selection_note);

        return [
            'application' => $application,
            'project' => $application->project,
            'umkm' => $application->project?->umkmProfile?->business_name,
            'appliedAt' => $this->format($application->created_at, 'j M Y'),
            'note' => $note !== '' ? $note : null,
            'timeline' => $timeline,
            // Terbuka otomatis hanya untuk lamaran yang masih aktif dan punya lebih dari satu entri.
            'timelineOpen' => $isActive && count($timeline) > 1,
            'cancelledAt' => $cancelled ? $this->format($cancelled->changed_at, 'j M Y') : null,
        ];
    }

    /**
     * Mengubah riwayat (terbaru -> terlama) menjadi entri timeline.
     * Entri paling awal berstatus Menunggu Keputusan diberi label "Lamaran dikirim" (dicatat oleh PBI-16).
     *
     * @param  Collection<int, ApplicationStatusHistory>  $histories
     * @return list<array{label: string, date: ?string, detail: ?string, note: ?string, noteLabel: string, dot: string}>
     */
    public function timeline(Collection $histories): array
    {
        $entries = [];

        foreach ($histories->values() as $index => $history) {
            $isInitial = $index === $histories->count() - 1 && $history->status === ApplicationStatus::Pending;
            $note = trim((string) $history->note);

            $entries[] = [
                'label' => $isInitial ? 'Lamaran dikirim' : Str::ucfirst(Str::lower($history->status->label())),
                'date' => $this->format($history->changed_at, 'j M Y, H.i'),
                'detail' => $isInitial ? 'Status awal: '.$history->status->label() : null,
                'note' => $note !== '' ? $note : null,
                'noteLabel' => $history->status === ApplicationStatus::Cancelled ? 'Catatan' : 'Catatan UMKM',
                'dot' => match (true) {
                    $isInitial, $history->status === ApplicationStatus::Accepted => 'g',
                    $history->status === ApplicationStatus::Rejected => 'r',
                    $history->status === ApplicationStatus::Cancelled => 'w',
                    default => '',
                },
            ];
        }

        return $entries;
    }

    /**
     * Dasar semua query: hanya lamaran milik profil kandidat ini.
     * Kandidat tanpa profil memakai id 0 yang tidak pernah cocok, sehingga hasilnya kosong.
     */
    private function baseQuery(?CandidateProfile $profile): Builder
    {
        return Application::query()->where('candidate_profile_id', $profile?->id ?? 0);
    }

    /** Contoh: "4 Nov 2026" atau "6 Nov 2026, 11.30". Null bila tanggal tidak tercatat. */
    private function format(?CarbonInterface $date, string $pattern): ?string
    {
        return $date?->copy()->locale('id')->translatedFormat($pattern);
    }
}
