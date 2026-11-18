<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-004 — Penerimaan ditolak karena kuota terisi oleh penerimaan lain (halaman detail dibuka sebelum kuota penuh)
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 1; Pelamar A (K1) dan Pelamar B (K2) melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab A: 'Pelamar berhasil diterima.' Tab B: 'Kuota kandidat untuk proyek ini sudah terpenuhi. Kandidat tidak dapat diterima lagi.' B tetap Menunggu Keputusan dan riwayatnya tidak bertambah. Daftar: 'Kuota kandidat terisi 1 dari 1' dan 'Kuota terpenuhi'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_004_Test.php
 */
class TC_PBI21_004_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_004(): void
    {
        $this->browse(function (Browser $tabA, Browser $tabB) {
            $umkm = $this->siapkanUmkmTerverifikasi($tabA, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($tabA, $umkm, 'Proyek', 1);
            $k1 = $this->daftarKandidat($tabA, 'K1');
            $this->lamar($tabA, $k1, $proyek);
            $k2 = $this->daftarKandidat($tabA, 'K2');
            $this->lamar($tabA, $k2, $proyek);

            // ===== Skenario utama =====
            // Tab A: buka Pelamar A
            $this->masuk($tabA, $umkm);
            $this->bukaSeleksi($tabA);
            $this->pilihProyek($tabA, $proyek['id']);
            $pelamarA = $this->lamaranIdDiTabel($tabA, $k1['nama']);
            $pelamarB = $this->lamaranIdDiTabel($tabA, $k2['nama']);
            $this->lihatPelamar($tabA, $pelamarA);

            // Tab B: buka Pelamar B (tidak di-refresh)
            $this->masuk($tabB, $umkm);
            $this->bukaSeleksi($tabB);
            $this->pilihProyek($tabB, $proyek['id']);
            $this->lihatPelamar($tabB, $pelamarB);
            $riwayatB = (int) $tabB->script("return document.querySelectorAll('.tl .it').length;")[0];

            // Tab A: terima Pelamar A
            $this->pilihKeputusan($tabA, 'accept');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Pelamar berhasil diterima.')->assertSeeIn('.prof .bd', 'Diterima');
            $this->bukti($tabA, 'TC_PBI21_004_tabA');

            // Tab B: terima Pelamar B
            $this->pilihKeputusan($tabB, 'accept');
            $this->simpanKeputusan($tabB);
            $tabB->assertSee('Kuota kandidat untuk proyek ini sudah terpenuhi. Kandidat tidak dapat diterima lagi.')
                ->assertSeeIn('.prof .bd', 'Menunggu Keputusan');
            $this->assertSame($riwayatB, (int) $tabB->script("return document.querySelectorAll('.tl .it').length;")[0]);
            $this->bukti($tabB, 'TC_PBI21_004_tabB');

            // Kembali ke daftar pelamar dan amati kuota
            $this->kembaliKeDaftar($tabB);
            $tabB->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 1')->assertSee('Kuota terpenuhi');
            $this->bukti($tabB, 'TC_PBI21_004_daftar');
        });
    }
}
