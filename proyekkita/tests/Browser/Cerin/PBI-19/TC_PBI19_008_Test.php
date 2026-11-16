<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-008 — Proyek tanpa pelamar menampilkan keadaan kosong
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit tanpa pelamar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Halaman terbuka tanpa error dan menampilkan 'Belum ada pelamar pada proyek ini'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_008_Test.php
 */
class TC_PBI19_008_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_008(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            $browser->assertSee('Belum ada pelamar pada proyek ini')->assertDontSee('Server Error');
            $this->assertSame([], $this->namaDiTabel($browser));
            $this->bukti($browser, 'TC_PBI19_008');
        });
    }
}
