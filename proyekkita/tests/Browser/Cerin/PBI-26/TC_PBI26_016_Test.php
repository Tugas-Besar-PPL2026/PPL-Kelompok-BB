<?php

namespace Tests\Browser\Cerin\PBI26;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI26-016 — ID kesepakatan tidak ada
 *
 * Jenis Test     : Negatif
 * Data disiapkan : UMKM terverifikasi.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Membuka URL detail kesepakatan dengan ID 999999 menampilkan halaman 404.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-26/TC_PBI26_016_Test.php
 */
class TC_PBI26_016_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi26_016(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $browser->visit('/umkm/kesepakatan/999999')
                ->assertSee('404')
                ->assertMissing('@detail-kesepakatan');
            $this->bukti($browser, 'TC_PBI26_016');
        });
    }
}
