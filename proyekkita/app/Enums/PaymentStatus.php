<?php

namespace App\Enums;

/**
 * Status pembayaran per termin (pembayaran dilakukan di luar aplikasi).
 * Nilai yang disimpan di kolom payments.status, lengkap dengan label dan warna badge untuk tampilan.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case Reported = 'reported';
    case Cancelled = 'cancelled';

    /** Label bahasa Indonesia yang tampil di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Dibayar',
            self::Paid => 'Menunggu Konfirmasi',
            self::Confirmed => 'Dikonfirmasi Diterima',
            self::Reported => 'Dilaporkan Belum Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Kelas warna badge di CSS (ok, warn, bad, info, grey, pur). */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'grey',
            self::Paid => 'info',
            self::Confirmed => 'ok',
            self::Reported => 'bad',
            self::Cancelled => 'grey',
        };
    }

    /** @return array<string, string> nilai => label, untuk select/filter */
    public static function options(): array
    {
        return array_column(array_map(fn (self $s) => ['v' => $s->value, 'l' => $s->label()], self::cases()), 'l', 'v');
    }
}
