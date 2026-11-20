<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-005 — Pelamar yang sudah Diterima tidak dapat ditolak (halaman detail dibuka sebelum status berubah)
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: 'Pelamar berhasil diterima.' Tab A: 'Pelamar yang sudah diterima tidak dapat diubah statusnya lewat alur ini.' Status tetap Diterima; tidak ada riwayat Ditolak.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_005_Test.php
 */
class TC_PBI20_005_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_005(): void
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
            $this->bukti($tabB, 'TC_PBI20_005_tabB');

            // Tab A
            $this->pilihKeputusan($tabA, 'reject');
            $this->isiCatatan($tabA, 'Belum sesuai kebutuhan');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Pelamar yang sudah diterima tidak dapat diubah statusnya lewat alur ini.')
                ->assertSeeIn('.prof .bd', 'Diterima');
            $this->assertStringNotContainsStringIgnoringCase('Ditolak', $tabA->script("return (document.querySelector('.tl')?.innerText || '').replace(/\\s+/g, ' ');")[0], 'Riwayat status Ditolak tidak boleh tersimpan.');
            $this->bukti($tabA, 'TC_PBI20_005_tabA');
        });
    }
}
