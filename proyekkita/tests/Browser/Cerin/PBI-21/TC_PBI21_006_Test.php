<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-006 — Kuota dihitung per proyek
 *
 * Jenis Test     : Positif
 * Data disiapkan : UMKM A punya 2 proyek terbit kuota 1; K1 melamar keduanya; K1 diterima di proyek pertama (penuh).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Penerimaan pada proyek kedua berhasil karena kuota proyek lain tidak ikut dihitung.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_006_Test.php
 */
class TC_PBI21_006_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_006(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyekPenuh = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek Penuh', 1);
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek Kedua', 1);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyekPenuh);
            $this->lamar($browser, $k1, $proyek);
            $this->putuskan($browser, $umkm, $proyekPenuh, $k1, 'accept');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);

            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.')->assertSeeIn('.prof .bd', 'Diterima');
            $this->bukti($browser, 'TC_PBI21_006');
        });
    }
}
