<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-008 — Pelamar yang sudah Ditolak tidak dapat diterima (halaman detail dibuka sebelum status berubah)
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: 'Pelamar ditolak dan catatan penolakan tersimpan.' Tab A: 'Lamaran ini sudah ditolak dan keputusannya tidak dapat diubah lagi.' Status tetap Ditolak; jumlah Diterima tidak bertambah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_008_Test.php
 */
class TC_PBI21_008_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_008(): void
    {
        $this->browse(function (Browser $tabA, Browser $tabB) {
            $umkm = $this->siapkanUmkmTerverifikasi($tabA, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($tabA, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($tabA, 'K1');
            $this->lamar($tabA, $k1, $proyek);
            // ===== Skenario utama =====
            // Tab A: buka detail pelamar (tidak di-refresh)
            $this->masuk($tabA, $umkm);
            $this->bukaSeleksi($tabA);
            $this->pilihProyek($tabA, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($tabA, $k1['nama']);
            $this->lihatPelamar($tabA, $lamaran);

            // Tab B: akun UMKM yang sama
            $this->masuk($tabB, $umkm);
            $this->bukaSeleksi($tabB);
            $this->pilihProyek($tabB, $proyek['id']);
            $this->lihatPelamar($tabB, $lamaran);
            $this->pilihKeputusan($tabB, 'reject');
            $this->isiCatatan($tabB, 'Keterampilan belum sesuai');
            $this->simpanKeputusan($tabB);
            $tabB->assertSee('Pelamar ditolak dan catatan penolakan tersimpan.');
            $this->bukti($tabB, 'TC_PBI21_008_tabB');

            // Tab A
            $this->pilihKeputusan($tabA, 'accept');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Lamaran ini sudah ditolak dan keputusannya tidak dapat diubah lagi.')
                ->assertSeeIn('.prof .bd', 'Ditolak');
            $this->bukti($tabA, 'TC_PBI21_008_tabA');

            $this->kembaliKeDaftar($tabA);
            $tabA->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 2');
        });
    }
}
