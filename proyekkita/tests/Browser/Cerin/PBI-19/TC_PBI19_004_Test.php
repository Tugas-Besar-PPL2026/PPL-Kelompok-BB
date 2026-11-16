<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-004 — Lamaran yang dibatalkan kandidat tidak dihitung dan tidak ditampilkan
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1, K2, K3 melamar; K3 membatalkan sendiri lewat Lamaran Saya.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Total pelamar 2. Kandidat yang membatalkan tidak muncul di tabel maupun di tab mana pun.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_004_Test.php
 */
class TC_PBI19_004_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_004(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $k3 = $this->daftarKandidat($browser, 'K3');
            $this->lamar($browser, $k3, $proyek);
            $this->batalkanSebagaiKandidat($browser, $k3, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            $this->assertSame('2', $this->statistik($browser)[0]);
            for ($tab = 1; $tab <= 5; $tab++) {
                $browser->waitForReload(fn (Browser $b) => $b->click("@tab-status a:nth-child({$tab})"), 20);
                $this->assertNotContains($k3['nama'], $this->namaDiTabel($browser), "Kandidat yang membatalkan muncul pada tab ke-{$tab}.");
            }
            $this->bukti($browser, 'TC_PBI19_004');
        });
    }
}
