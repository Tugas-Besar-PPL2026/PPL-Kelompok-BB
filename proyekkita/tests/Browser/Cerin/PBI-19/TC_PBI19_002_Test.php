<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-002 — Memfilter daftar pelamar dengan tab status
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 dan K2 melamar; K1 dimasukkan ke Terpilih Sementara.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Hanya pelamar berstatus Terpilih Sementara yang tampil.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_002_Test.php
 */
class TC_PBI19_002_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_002(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'shortlist');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            // Klik tab Terpilih Sementara (tab ke-3)
            $browser->waitForReload(fn (Browser $b) => $b->click('@tab-status a:nth-child(3)'), 20)
                ->assertQueryStringHas('status', 'shortlisted');
            $this->assertSame([$k1['nama']], $this->namaDiTabel($browser));
            $this->assertSame(['Terpilih Sementara'], $this->badgeDiTabel($browser));
            $this->bukti($browser, 'TC_PBI19_002');
        });
    }
}
