<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Format tampilan berbahasa Indonesia: tanggal, jam, rating, dan durasi pengalaman.
 * Semua method statis dan tidak bergantung pada locale Carbon, jadi hasilnya selalu sama di setiap environment.
 */
class IdFormat
{
    /** Zona waktu yang ditampilkan ke pengguna (data disimpan sesuai timezone aplikasi). */
    public const DISPLAY_TZ = 'Asia/Jakarta';

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Contoh: 4 Nov 2026 */
    public static function date(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '-';
        }

        $local = $date->copy()->setTimezone(self::DISPLAY_TZ);

        return $local->day.' '.self::MONTHS[$local->month - 1].' '.$local->year;
    }

    /** Contoh: 4 Nov 2026, 20.11 */
    public static function dateTime(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '-';
        }

        return self::date($date).', '.$date->copy()->setTimezone(self::DISPLAY_TZ)->format('H.i');
    }

    /** Rating satu desimal dengan koma, contoh: 4,7 */
    public static function rating(float|int $rating): string
    {
        return number_format((float) $rating, 1, ',', '.');
    }

    /**
     * Mengubah teks durasi bebas ("8 bulan", "1 thn 2 bln", "1,5 tahun") menjadi jumlah bulan.
     * Mengembalikan null jika tidak ada angka + satuan (tahun/bulan) yang dapat dibaca.
     */
    public static function durationToMonths(?string $text): ?int
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $found = preg_match_all(
            '/(\d+(?:[.,]\d+)?)\s*(tahun|thn|years?|yr|th|bulan|bln|months?|mo)(?![a-z])/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        if (! $found) {
            return null;
        }

        $months = 0.0;
        foreach ($matches as [, $number, $unit]) {
            $value = (float) str_replace(',', '.', $number);
            $isYear = in_array(mb_strtolower($unit), ['tahun', 'thn', 'year', 'years', 'yr', 'th'], true);
            $months += $isYear ? $value * 12 : $value;
        }

        return (int) round($months);
    }

    /** Contoh: 14 => "1 thn 2 bln", 10 => "10 bln", 12 => "1 thn". */
    public static function months(int $months): string
    {
        $years = intdiv($months, 12);
        $rest = $months % 12;

        return trim(($years > 0 ? $years.' thn' : '').' '.($rest > 0 ? $rest.' bln' : ''));
    }
}
