<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-012 — Progress bar menjadi 100% saat kuota terpenuhi
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 dan K2 melamar; K1 diterima.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Daftar: 'Kuota kandidat terisi 2 dari 2', 'Kuota terpenuhi', progress bar 100%. Detail: '2 dari 2 terisi' dan progress bar 100%.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_012_Test.php
 */
class TC_PBI21_012_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_012(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'accept');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k2['nama']));

            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.')
                ->assertSeeIn('@kuota-panel', '2 dari 2 terisi')
                ->assertAttribute('@progres-kuota-panel', 'aria-valuenow', '100');
            $this->bukti($browser, 'TC_PBI21_012_detail');

            $this->kembaliKeDaftar($browser);
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 2 dari 2')
                ->assertSee('Kuota terpenuhi')
                ->assertAttribute('@progres-kuota-daftar', 'aria-valuenow', '100');
            $this->bukti($browser, 'TC_PBI21_012_daftar');
        });
    }
}
