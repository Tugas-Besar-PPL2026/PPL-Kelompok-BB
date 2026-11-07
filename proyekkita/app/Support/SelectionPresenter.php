<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SelectionPresenter
{
    /** Huruf awal dari maksimal dua kata pertama pada nama, mis. "Nadia Putri Rahma" -> "NP". */
    public static function initials(?string $name): string
    {
        return collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->join('');
    }

    /** Contoh: "4 Nov 2026". */
    public static function date(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat('j M Y') : '-';
    }

    /** Contoh: "4 Nov 2026, 20.11". */
    public static function dateTime(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat('j M Y, H.i') : '-';
    }

    /** Angka desimal gaya Indonesia, mis. 4.7 -> "4,7". */
    public static function decimal(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    /**
     * Mengubah teks durasi bebas pada tabel experiences (mis. "6 bulan", "1 tahun 2 bulan", "8 bln")
     * menjadi jumlah bulan. Mengembalikan null bila tidak ada satuan waktu yang dikenali.
     */
    public static function parseMonths(?string $text): ?float
    {
        if ($text === null || ! preg_match_all('/(\d+(?:[.,]\d+)?)\s*(tahun|thn|th|bulan|bln|minggu|mgg|hari|hr)\b/iu', $text, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $months = 0.0;
        foreach ($matches as [, $number, $unit]) {
            $value = (float) str_replace(',', '.', $number);
            $months += match (mb_strtolower($unit)) {
                'tahun', 'thn', 'th' => $value * 12,
                'bulan', 'bln' => $value,
                'minggu', 'mgg' => $value / 4.345,
                default => $value / 30,
            };
        }

        return $months;
    }

    /** Contoh: 14 -> "1 thn 2 bln", 10 -> "10 bln", 12 -> "1 thn". Null bila tidak ada durasi. */
    public static function monthsLabel(float $months): ?string
    {
        if ($months <= 0) {
            return null;
        }

        $total = (int) round($months);
        if ($total === 0) {
            return '< 1 bln';
        }

        $parts = [];
        if (intdiv($total, 12) > 0) {
            $parts[] = intdiv($total, 12).' thn';
        }
        if ($total % 12 > 0) {
            $parts[] = ($total % 12).' bln';
        }

        return implode(' ', $parts);
    }

    /**
     * Ringkasan pengalaman kandidat: jumlah pengalaman dan total durasi dari yang durasinya terbaca.
     *
     * @param  Collection<int, \App\Models\Experience>  $experiences
     * @return array{count: int, duration: ?string}
     */
    public static function experienceSummary(Collection $experiences): array
    {
        $months = $experiences->sum(fn ($experience) => self::parseMonths($experience->duration) ?? 0);

        return [
            'count' => $experiences->count(),
            'duration' => self::monthsLabel((float) $months),
        ];
    }

    /** Contoh: 4300000 -> "4,1 MB". */
    public static function fileSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1024 * 1024 => self::decimal($bytes / (1024 * 1024)).' MB',
            $bytes >= 1024 => self::decimal($bytes / 1024).' KB',
            default => $bytes.' B',
        };
    }

    /**
     * Menormalkan tautan portofolio menjadi URL http/https yang aman dibuka.
     * Tautan tanpa skema ("behance.net/x") diberi https://. Skema lain (javascript:, data:, dst.) ditolak.
     */
    public static function safeUrl(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '' || preg_match('/[\x00-\x20\x7F]/', $link)) {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link) || preg_match('#^[a-z0-9.\-]+:\d+(/|$)#i', $link)) {
            $link = 'https://'.ltrim($link, '/');
        }

        $parts = parse_url($link);
        if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        return filter_var($link, FILTER_VALIDATE_URL) ? $link : null;
    }

    /** Label singkat tautan: tanpa skema, tanpa "www.", tanpa garis miring penutup. */
    public static function displayUrl(string $url): string
    {
        return rtrim(preg_replace('#^https?://(www\.)?#i', '', $url), '/');
    }
}
