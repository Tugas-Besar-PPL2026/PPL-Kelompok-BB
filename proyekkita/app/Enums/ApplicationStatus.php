<?php

namespace App\Enums;

/**
 * Status lamaran kandidat.
 * Nilai yang disimpan di kolom applications.status dan application_status_histories.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Keputusan',
            self::Shortlisted => 'Terpilih Sementara',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Shortlisted => 'pur',
            self::Accepted => 'ok',
            self::Rejected => 'bad',
            self::Cancelled => 'grey',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
