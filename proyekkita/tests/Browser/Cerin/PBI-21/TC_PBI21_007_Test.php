<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-007 — Pelamar yang sama tidak dapat diterima dua kali (halaman detail dibuka sebelum pelamar diterima)
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: 'Pelamar berhasil diterima.' Tab A: 'Pelamar yang sudah diterima tidak dapat diubah statusnya lewat alur ini.' Riwayat Diterima hanya satu kali. Daftar 'Kuota kandidat terisi 1 dari 2'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_007_Test.php
 */
class TC_PBI21_007_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_007(): void
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
            $this->pilihKeputusan($tabB, 'accept');
            $this->simpanKeputusan($tabB);
            $tabB->assertSee('Pelamar berhasil diterima.');
            $this->bukti($tabB, 'TC_PBI21_007_tabB');

            // Tab A
            $this->pilihKeputusan($tabA, 'accept');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Pelamar yang sudah diterima tidak dapat diubah statusnya lewat alur ini.');
            $diterima = (int) $tabA->script("return [...document.querySelectorAll('.tl .it')].filter(i => (i.querySelector('.t1')?.innerText || '').trim() === 'Diterima').length;")[0];
            $this->assertSame(1, $diterima, 'Riwayat Diterima tersimpan lebih dari satu kali.');
            $this->bukti($tabA, 'TC_PBI21_007_tabA');

            $this->kembaliKeDaftar($tabA);
            $tabA->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 2');
            $this->bukti($tabA, 'TC_PBI21_007_daftar');
        });
    }
}