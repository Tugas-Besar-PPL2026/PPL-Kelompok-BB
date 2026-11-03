<?php

namespace App\Enums;

/**
 * Status peninjauan hasil pekerjaan.
 * Nilai yang disimpan di kolom submissions.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case RevisionRequested = 'revision_requested';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Menunggu Tinjauan',
            self::Approved => 'Disetujui',
            self::RevisionRequested => 'Perlu Revisi',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::Approved => 'ok',
            self::RevisionRequested => 'warn',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
