<?php

namespace App\Support;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Models\Project;
use Carbon\CarbonInterface;

/**
 * [PBI-24, PBI-25, PBI-26] Fungsi bantu tampilan kesepakatan pekerjaan: kode kesepakatan, format rupiah,
 * daftar hasil yang diserahkan, tabel tahapan pembayaran, sisa hari menuju tenggat, dan linimasa status.
 * Tidak mengubah data apa pun.
 */
class AgreementPresenter
{
    /** Kode tampilan, mis. KSP-2026-018 (tahun lamaran + nomor kesepakatan). */
    public static function code(Agreement $agreement): string
    {
        $year = $agreement->application?->created_at?->format('Y') ?? now()->format('Y');

        return sprintf('KSP-%s-%03d', $year, $agreement->id);
    }

    /** Contoh: 2500000 -> "Rp2.500.000". */
    public static function rupiah(float|int|string|null $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }

    /** Hasil yang harus diserahkan ditulis satu per baris; tanda butir di awal baris dibuang. */
    public static function deliverables(?string $text): array
    {
        return collect(preg_split('/\R/u', (string) $text))
            ->map(fn ($line) => trim(preg_replace('/^\s*([•\-*]|\d+[.)])\s*/u', '', $line)))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * Tahapan pembayaran dari detail proyek (PBI-10). Kesepakatan tidak menyimpan termin sendiri,
     * sehingga kompensasi dan termin selalu sama dengan yang dipublikasikan pada proyek.
     *
     * @return array{rows: list<array{no: int, condition: string, amount: string, percent: string}>, total: string, count: int}
     */
    public static function terms(Project $project): array
    {
        $total = (float) $project->total_compensation;
        $rows = $project->paymentTerms->map(fn ($term) => [
            'no' => (int) $term->term_number,
            'condition' => (string) $term->condition,
            'amount' => self::rupiah($term->amount),
            'percent' => $total > 0 ? round((float) $term->amount / $total * 100).'%' : '-',
        ])->values()->all();

        return ['rows' => $rows, 'total' => self::rupiah($total), 'count' => count($rows)];
    }

    /** Tanggal tenggat beserta keterangan sisa hari, mis. ["15 Des 2026", "23 hari lagi"]. */
    public static function deadline(?CarbonInterface $deadline): array
    {
        if (! $deadline) {
            return ['date' => '-', 'note' => 'Belum ditentukan', 'late' => false];
        }

        $days = (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        return [
            'date' => SelectionPresenter::date($deadline),
            'note' => match (true) {
                $days > 0 => "{$days} hari lagi",
                $days === 0 => 'Hari ini',
                default => 'Lewat '.abs($days).' hari',
            },
            'late' => $days < 0,
        ];
    }

    /**
     * Linimasa status kesepakatan untuk halaman detail (mockup 4.5.24 dan 4.5.25).
     * Tahap setelah "Kesepakatan aktif" (pengerjaan, peninjauan, pembayaran, selesai) dikelola PBI lain,
     * sehingga di sini hanya ditandai berdasarkan status kesepakatan.
     *
     * @return list<array{label: string, note: string, state: string}>
     */
    public static function stages(Agreement $agreement): array
    {
        $status = $agreement->status;
        $active = in_array($status, [AgreementStatus::Active, AgreementStatus::Completed], true);
        $done = $status === AgreementStatus::Completed;

        return [
            [
                'label' => 'Kesepakatan aktif',
                'note' => $agreement->approved_at
                    ? 'Disetujui '.SelectionPresenter::date($agreement->approved_at)
                    : ($status === AgreementStatus::Sent ? 'Menunggu persetujuan kandidat' : 'Belum aktif'),
                'state' => $active ? 'done' : ($status === AgreementStatus::Sent ? 'current' : 'todo'),
            ],
            ['label' => 'Pengerjaan', 'note' => $active ? 'Pekerjaan berjalan' : 'Menunggu kesepakatan aktif', 'state' => $done ? 'done' : ($active ? 'current' : 'todo')],
            ['label' => 'Peninjauan hasil', 'note' => $done ? 'Hasil disetujui' : 'Menunggu tahapan sebelumnya', 'state' => $done ? 'done' : 'todo'],
            ['label' => 'Pembayaran', 'note' => $done ? 'Seluruh termin dikonfirmasi' : 'Menunggu tahapan sebelumnya', 'state' => $done ? 'done' : 'todo'],
            ['label' => 'Selesai', 'note' => $done ? 'Kesepakatan selesai' : 'Menunggu tahapan sebelumnya', 'state' => $done ? 'done' : 'todo'],
        ];
    }

    /** Persentase progres terakhir (PBI-29) bila sudah ada laporan; null bila belum ada. Hanya dibaca. */
    public static function latestProgress(Agreement $agreement): ?int
    {
        $latest = $agreement->relationLoaded('progressUpdates')
            ? $agreement->progressUpdates->first()
            : $agreement->progressUpdates()->first();

        return $latest ? (int) $latest->percentage : null;
    }
}
