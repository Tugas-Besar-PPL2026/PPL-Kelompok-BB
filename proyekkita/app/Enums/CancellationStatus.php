<?php

namespace App\Enums;

/**
 * Status pengajuan pembatalan kesepakatan.
 * Nilai yang disimpan di kolom agreement_cancellations.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum CancellationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Tanggapan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Approved => 'ok',
            self::Rejected => 'bad',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
