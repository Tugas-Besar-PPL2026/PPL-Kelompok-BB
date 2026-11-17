<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-010 — Statistik, kuota, dan tab pada daftar pelamar diperbarui setelah penerimaan
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 3; K1 dan K2 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kuota terisi 1 dari 3; statistik Diterima 1; Menunggu 1.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_010_Test.php
 */
class TC_PBI21_010_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_010(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 3);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);

            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.');

            // Kembali ke menu Seleksi Kandidat
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 3');
            $statistik = $this->statistik($browser);
            $this->assertSame('1 / 3', $statistik[3]);
            $this->assertSame('1', $statistik[1]);
            $tab = $this->tabStatus($browser);
            $this->assertContains('Diterima 1', $tab);
            $this->assertContains('Menunggu Keputusan 1', $tab);
            $this->bukti($browser, 'TC_PBI21_010');
        });
    }
}
