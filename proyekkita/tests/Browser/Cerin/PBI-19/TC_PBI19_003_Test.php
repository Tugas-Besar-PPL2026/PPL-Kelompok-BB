<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-003 — Nilai filter status yang tidak dikenal diperlakukan sebagai Semua
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Halaman tetap terbuka tanpa error dan menampilkan semua pelamar.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_003_Test.php
 */
class TC_PBI19_003_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_003(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/seleksi?proyek={$proyek['id']}&status=asal")
                ->assertPathIs('/umkm/seleksi')
                ->assertDontSee('Server Error')
                ->assertAttribute('@tab-status a:nth-child(1)', 'aria-current', 'page');
            $this->assertSame([$k1['nama']], $this->namaDiTabel($browser));
            $this->bukti($browser, 'TC_PBI19_003');
        });
    }
}
