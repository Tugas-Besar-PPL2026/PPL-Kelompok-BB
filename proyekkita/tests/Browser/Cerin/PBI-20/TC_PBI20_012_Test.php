<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-012 — Lamaran yang sudah dibatalkan kandidat tidak dapat dimasukkan ke Terpilih Sementara
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tab B: status lamaran menjadi Dibatalkan. Tab A: 'Lamaran ini sudah dibatalkan oleh kandidat sehingga tidak dapat diproses.' Status tetap Dibatalkan; tidak ada riwayat Terpilih Sementara.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_012_Test.php
 */
class TC_PBI20_012_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_012(): void
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
            $this->bukti($tabB, 'TC_PBI20_012_tabB');

            // Tab A (UMKM)
            $this->pilihKeputusan($tabA, 'shortlist');
            $this->simpanKeputusan($tabA);
            $tabA->assertSee('Lamaran ini sudah dibatalkan oleh kandidat sehingga tidak dapat diproses.')
                ->assertSeeIn('.prof .bd', 'Dibatalkan');
            $this->assertStringNotContainsStringIgnoringCase('Terpilih Sementara', $tabA->script("return (document.querySelector('.tl')?.innerText || '').replace(/\\s+/g, ' ');")[0], 'Riwayat status Terpilih Sementara tidak boleh tersimpan.');
            $this->bukti($tabA, 'TC_PBI20_012_tabA');
        });
    }
}
