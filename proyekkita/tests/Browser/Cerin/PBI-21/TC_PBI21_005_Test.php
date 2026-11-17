<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-005 — Hanya status Diterima yang dihitung sebagai kuota
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 3; K1..K5 melamar; K1 & K2 Terpilih Sementara, K3 Ditolak, K4 membatalkan sendiri, K5 Menunggu.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Penerimaan berhasil; jumlah Diterima 1.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_005_Test.php
 */
class TC_PBI21_005_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_005(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 3);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $k3 = $this->daftarKandidat($browser, 'K3');
            $this->lamar($browser, $k3, $proyek);
            $k4 = $this->daftarKandidat($browser, 'K4');
            $this->lamar($browser, $k4, $proyek);
            $k5 = $this->daftarKandidat($browser, 'K5');
            $this->lamar($browser, $k5, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'shortlist');
            $this->putuskan($browser, $umkm, $proyek, $k2, 'shortlist');
            $this->putuskan($browser, $umkm, $proyek, $k3, 'reject', 'Catatan penolakan persiapan data');
            $this->batalkanSebagaiKandidat($browser, $k4, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k5['nama']));
            $this->pilihKeputusan($browser, 'accept');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar berhasil diterima.');

            $this->kembaliKeDaftar($browser);
            $this->assertSame('1 / 3', $this->statistik($browser)[3]);
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 3');
            $this->bukti($browser, 'TC_PBI21_005');
        });
    }
}
