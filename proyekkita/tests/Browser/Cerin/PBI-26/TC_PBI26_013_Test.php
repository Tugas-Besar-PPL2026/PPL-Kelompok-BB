<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-013 — UMKM membuka detail milik UMKM lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : Kesepakatan Aktif pada proyek UMKM A; UMKM B terverifikasi.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : UMKM B membuka URL detail milik UMKM A: halaman 403, data tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_013_Test.php
 */
class TC_PBI26_013_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_013(): void
    {
        $this->browse(function (Browser $browser) {
            $d = $this->siapkanKesepakatan($browser, 'aktif');
            $umkmB = $this->siapkanUmkmTerverifikasi($browser, 'UMKM B');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkmB);
            $browser->visit("/umkm/kesepakatan/{$d['kesepakatan']}")
                ->assertSee('403')
                ->assertMissing('@detail-kesepakatan')
                ->assertDontSee(self::RUANG_LINGKUP);
            $this->bukti($browser, 'TC_PBI26_013');
        });
    }
}
