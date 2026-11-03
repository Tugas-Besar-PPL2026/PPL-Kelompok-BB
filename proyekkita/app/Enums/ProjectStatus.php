<?php

namespace App\Enums;

/**
 * Status proyek, mulai dari draf hingga ditutup.
 * Nilai yang disimpan di kolom projects.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum ProjectStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case RevisionRequested = 'revision_requested';
    case Rejected = 'rejected';
    case Published = 'published';
    case Closed = 'closed';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingReview => 'Menunggu Moderasi',
            self::RevisionRequested => 'Perlu Perbaikan',
            self::Rejected => 'Ditolak',
            self::Published => 'Dipublikasikan',
            self::Closed => 'Ditutup',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'grey',
            self::PendingReview => 'info',
            self::RevisionRequested => 'warn',
            self::Rejected => 'bad',
            self::Published => 'ok',
            self::Closed => 'grey',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
