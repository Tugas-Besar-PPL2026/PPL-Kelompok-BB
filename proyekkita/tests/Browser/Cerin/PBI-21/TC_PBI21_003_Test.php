<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-003 — Menerima pelamar saat sisa kuota satu
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 1; K1 dan K2 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Pelamar pertama berstatus Diterima. Daftar menampilkan 'Kuota kandidat terisi 1 dari 1' dan 'Kuota terpenuhi'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_003_Test.php
 */
class TC_PBI21_003_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_003(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 1);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            $lamaran = $this->lamaranIdDiTabel($browser, $this->namaDiTabel($browser)[0]);
            $this->lihatPelamar($browser, $lamaran);
            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.');

            $this->kembaliKeDaftar($browser);
            $browser->assertSeeIn("@status-pelamar-{$lamaran}", 'Diterima')
                ->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 1')
                ->assertSee('Kuota terpenuhi');
            $this->bukti($browser, 'TC_PBI21_003');
        });
    }
}
