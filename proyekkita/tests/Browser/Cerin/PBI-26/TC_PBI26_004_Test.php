<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-004 — UMKM membuka detail
 *
 * Jenis Test     : Positif
 * Data disiapkan : UMKM punya 1 kesepakatan Aktif dengan Kandidat 1 (tenggat 15-12-2026).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kode dan label Aktif, judul proyek, para pihak, tenggat dan sisa hari, linimasa (Kesepakatan aktif selesai), serta Ringkasan Kesepakatan tampil sesuai isi yang dikirim.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_004_Test.php
 */
class TC_PBI26_004_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_004(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');
            $tenggat = $this->tenggat();
            $sisa = (int) now()->startOfDay()->diffInDays($tenggat->copy()->startOfDay(), false);

            // ===== Skenario utama =====
            $this->masuk($browser, $d['umkm']);
            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->klikTab($browser, 'active');
            $browser->click("@buka-kesepakatan-{$d['kesepakatan']}")
                ->waitFor('@detail-kesepakatan', 20)
                ->assertSeeIn('@kode-kesepakatan', 'KSP-')
                ->assertSeeIn('@detail-kesepakatan @status-kesepakatan', 'Aktif')
                ->assertSeeIn('@judul-proyek', $d['proyek']['judul'])
                ->assertSeeIn('@para-pihak', $d['kandidat']['nama'])
                ->assertSeeIn('@tenggat-detail', $this->tanggalTampil($tenggat))
                ->assertSeeIn('@sisa-hari', $sisa > 0 ? "{$sisa} hari lagi" : 'Hari ini')
                ->assertSeeIn('@linimasa-kesepakatan .ks-step.done', 'Kesepakatan aktif')
                ->assertSeeIn('@ringkasan-ruang-lingkup', self::RUANG_LINGKUP)
                ->assertSeeIn('@ringkasan-hasil', self::HASIL[1])
                ->assertSeeIn('@ringkasan-kompensasi', 'Rp2.500.000 · 2 termin')
                ->assertSeeIn('@ringkasan-revisi', '2 kali');
            $this->bukti($browser, 'TC_PBI26_004');
        });
    }
}
