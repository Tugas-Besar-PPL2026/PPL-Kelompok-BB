<?php

namespace App\Support;

use App\Models\CandidateProfile;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Storage;

/**
 * Pembantu tampilan data kandidat: inisial, foto, serta tautan dan dokumen portofolio yang aman dibuka.
 */
class CandidatePresenter
{
    /** Disk tempat foto profil dan dokumen portofolio disimpan (diakses publik lewat `php artisan storage:link`). */
    public const DISK = 'public';

    /** Dua huruf awal nama, contoh: "Nadia Putri Rahma" => "NP". */
    public static function initials(?string $name): string
    {
        $words = array_slice(array_filter(preg_split('/\s+/', trim((string) $name))), 0, 2);
        $letters = implode('', array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)), $words));

        return $letters !== '' ? $letters : '?';
    }

    /** URL foto profil bila file-nya ada, selain itu null (tampilan memakai inisial). */
    public static function photoUrl(CandidateProfile $profile): ?string
    {
        return self::existingFileUrl($profile->photo);
    }

    /**
     * Tautan portofolio yang aman: hanya http/https. Tautan tanpa skema (contoh "behance.net/x") diberi https://.
     * Skema lain seperti javascript: ditolak (null).
     */
    public static function externalUrl(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) {
            $link = 'https://'.ltrim($link, '/');
        }

        $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true) || parse_url($link, PHP_URL_HOST) === null) {
            return null;
        }

        return $link;
    }

    /** Teks tautan tanpa skema untuk ditampilkan, contoh "behance.net/nadiaputri/sambal". */
    public static function linkLabel(?string $link): string
    {
        return rtrim((string) preg_replace('#^https?://(www\.)?#i', '', trim((string) $link)), '/');
    }

    /**
     * Informasi dokumen portofolio: nama berkas, URL (null bila berkas tidak ada di penyimpanan), dan ukuran.
     *
     * @return array{name: string, url: ?string, size: ?string}|null
     */
    public static function file(Portfolio $portfolio): ?array
    {
        $path = $portfolio->file_path;
        if (! $path) {
            return null;
        }

        $url = self::existingFileUrl($path);
        $size = null;
        if ($url !== null) {
            try {
                $size = self::bytes(Storage::disk(self::DISK)->size($path));
            } catch (\Throwable) {
                $size = null;
            }
        }

        return ['name' => basename($path), 'url' => $url, 'size' => $size];
    }

    private static function existingFileUrl(?string $path): ?string
    {
        if (! $path || str_contains($path, '..')) {
            return null;
        }

        try {
            $disk = Storage::disk(self::DISK);

            return $disk->exists($path) ? $disk->url($path) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function bytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1, ',', '.').' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 0, ',', '.').' KB',
            default => $bytes.' B',
        };
    }
}
