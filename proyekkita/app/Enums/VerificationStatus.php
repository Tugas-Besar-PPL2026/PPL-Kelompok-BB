<?php

namespace App\Enums;

/**
 * Status verifikasi akun UMKM.
 * Nilai yang disimpan di kolom umkm_profiles.verification_status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pemeriksaan',
            self::Verified => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Verified => 'ok',
            self::Rejected => 'bad',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
