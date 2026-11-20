<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-013 — Lamaran yang sudah dibatalkan kandidat tidak dapat diterima
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: status lamaran menjadi Dibatalkan. Tab A: 'Lamaran ini sudah dibatalkan oleh kandidat sehingga tidak dapat diproses.' Status tetap Dibatalkan; jumlah Diterima tidak bertambah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_013_Test.php
 */
class TC_PBI21_013_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_013(): void
    {
        $this->browse(function (Browser $tabA, Browser $tabB) {
            $umkm = $this->siapkanUmkmTerverifikasi($tabA, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($tabA, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($tabA, 'K1');
            $this->lamar($tabA, $k1, $proyek);

            // ===== Skenario utama =====
            // Tab A (UMKM): buka detail pelamar
            $this->masuk($tabA, $umkm);
            $this->bukaSeleksi($tabA);
            $this->pilihProyek($tabA, $proyek['id']);
            $this->lihatPelamar($tabA, $this->lamaranIdDiTabel($tabA, $k1['nama']));

            // Tab B (Kandidat): batalkan lamaran
            $this->masuk($tabB, $k1);
            $this->bukaLamaranSaya($tabB);
            $this->batalkanLamaran($tabB, $this->lamaranIdDiLamaranSaya($tabB, $proyek['judul']));
            $this->bukti($tabB, 'TC_PBI21_013_tabB');

            // Tab A (UMKM): terima pelamar
            $this->pilihKeputusan($tabA, 'accept');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Lamaran ini sudah dibatalkan oleh kandidat sehingga tidak dapat diproses.')
                ->assertSeeIn('.prof .bd', 'Dibatalkan');
            $this->bukti($tabA, 'TC_PBI21_013_tabA');

            $this->kembaliKeDaftar($tabA);
            $tabA->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 2');
        });
    }
}
