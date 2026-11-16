<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-017 — Parameter ID non-numerik pada URL detail
 *
 * Jenis Test     : Negatif
 * Data disiapkan : UMKM A didaftarkan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Halaman tidak ditemukan (404).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_017_Test.php
 */
class TC_PBI19_017_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_017(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->daftarUmkm($browser, 'UMKM A');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $browser->visit('/umkm/seleksi/pelamar/abc')->assertSee('404');
            $this->bukti($browser, 'TC_PBI19_017');
        });
    }
}
