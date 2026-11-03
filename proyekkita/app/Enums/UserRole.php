<?php

namespace App\Enums;

/**
 * Peran pengguna.
 * Nilai yang disimpan di kolom users.role, lengkap dengan label dan warna badge untuk tampilan.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Umkm = 'umkm';
    case Kandidat = 'kandidat';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Umkm => 'UMKM',
            self::Kandidat => 'Kandidat',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Admin => 'info',
            self::Umkm => 'info',
            self::Kandidat => 'info',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
