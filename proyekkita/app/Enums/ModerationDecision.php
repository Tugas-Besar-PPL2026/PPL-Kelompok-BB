<?php

namespace App\Enums;

/**
 * Keputusan admin saat memeriksa proyek.
 * Nilai yang disimpan di kolom project_moderations.decision, lengkap dengan label dan warna badge untuk tampilan.
 */
enum ModerationDecision: string
{
    case Approved = 'approved';
    case RevisionRequested = 'revision_requested';
    case Rejected = 'rejected';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Disetujui',
            self::RevisionRequested => 'Minta Perbaikan',
            self::Rejected => 'Ditolak',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Approved => 'ok',
            self::RevisionRequested => 'warn',
            self::Rejected => 'bad',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
