<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-006 — UMKM membuka pelamar proyek milik UMKM lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : UMKM A dan UMKM B terverifikasi, masing-masing punya 1 proyek terbit.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Akses ditolak (403) dan data pelamar tidak tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_006_Test.php
 */
class TC_PBI19_006_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_006(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $this->siapkanProyekTerbit($browser, $umkm, 'Proyek A', 1);
            $umkmB = $this->siapkanUmkmTerverifikasi($browser, 'UMKM B');
            $proyekB = $this->siapkanProyekTerbit($browser, $umkmB, 'Proyek B', 1);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/seleksi?proyek={$proyekB['id']}")
                ->assertSee('403')
                ->assertMissing('table.t.pl')
                ->assertMissing('@kuota-daftar');
            $this->bukti($browser, 'TC_PBI19_006');
        });
    }
}
