<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-007 — Kandidat mencoba membuka halaman Seleksi Kandidat
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 akun kandidat didaftarkan lewat UI.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Akses ditolak (403).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_007_Test.php
 */
class TC_PBI19_007_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_007(): void
    {
        $this->browse(function (Browser $browser) {
            $k1 = $this->daftarKandidat($browser, 'K1');

            // ===== Skenario utama =====
            $this->masuk($browser, $k1);
            $browser->visit('/umkm/seleksi')
                ->assertSee('403')
                ->assertMissing('#pilih-proyek')
                ->assertMissing('table.t.pl');
            $this->bukti($browser, 'TC_PBI19_007');
        });
    }
}
