<?php

namespace App\Enums;

/**
 * Status kesepakatan pekerjaan.
 * Nilai yang disimpan di kolom agreements.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum AgreementStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Active = 'active';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Sent => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'grey',
            self::Sent => 'warn',
            self::Active => 'ok',
            self::Rejected => 'bad',
            self::Completed => 'ok',
            self::Cancelled => 'grey',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
