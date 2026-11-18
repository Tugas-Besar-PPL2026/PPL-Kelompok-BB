<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-006 — Pelamar yang sudah Ditolak tidak dapat dimasukkan ke Terpilih Sementara (halaman detail dibuka sebelum status berubah)
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: 'Pelamar ditolak dan catatan penolakan tersimpan.' Tab A: 'Lamaran ini sudah ditolak dan keputusannya tidak dapat diubah lagi.' Status tetap Ditolak; tidak ada riwayat Terpilih Sementara.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_006_Test.php
 */
class TC_PBI20_006_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_006(): void
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
            $this->isiCatatan($tabB, 'Portofolio belum relevan');
            $this->simpanKeputusan($tabB);
            $tabB->assertSee('Pelamar ditolak dan catatan penolakan tersimpan.');
            $this->bukti($tabB, 'TC_PBI20_006_tabB');

            // Tab A
            $this->pilihKeputusan($tabA, 'shortlist');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Lamaran ini sudah ditolak dan keputusannya tidak dapat diubah lagi.')
                ->assertSeeIn('.prof .bd', 'Ditolak');
            $this->assertStringNotContainsStringIgnoringCase('Terpilih Sementara', $tabA->script("return (document.querySelector('.tl')?.innerText || '').replace(/\\s+/g, ' ');")[0], 'Riwayat status Terpilih Sementara tidak boleh tersimpan.');
            $this->bukti($tabA, 'TC_PBI20_006_tabA');
        });
    }
}
