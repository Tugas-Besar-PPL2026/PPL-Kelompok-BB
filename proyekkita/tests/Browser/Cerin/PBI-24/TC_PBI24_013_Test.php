<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-013 — Akses oleh UMKM lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kandidat Diterima pada proyek UMKM A; draf kesepakatan sudah ada; UMKM B terverifikasi.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : UMKM B membuka URL draf kesepakatan milik UMKM A: halaman 403, data tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_013_Test.php
 */
class TC_PBI24_013_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_013(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'draf');
            $umkmB = $this->siapkanUmkmTerverifikasi($browser, 'UMKM B');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkmB);
            $browser->visit("/umkm/kesepakatan/{$d['kesepakatan']}/susun")
                ->assertSee('403')
                ->assertMissing('@form-kesepakatan')
                ->assertDontSee($d['kandidat']['nama']);
            $this->bukti($browser, 'TC_PBI24_013');
        });
    }
}
