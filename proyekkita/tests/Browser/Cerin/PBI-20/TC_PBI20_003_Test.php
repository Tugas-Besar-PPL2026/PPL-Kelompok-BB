<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-003 — Menolak pelamar dengan catatan kosong atau hanya spasi
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Muncul pesan 'Catatan penolakan wajib diisi agar kandidat mengetahui alasannya.'; status dan riwayat tidak berubah.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_003_Test.php
 */
class TC_PBI20_003_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_003(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);
            $riwayatAwal = (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0];

            foreach (['kosong' => '', 'spasi' => '   '] as $variasi => $catatan) {
                $this->pilihKeputusan($browser, 'reject');
                $this->isiCatatan($browser, $catatan);
                $this->simpanKeputusan($browser);

                $browser->assertSeeIn('@galat-keputusan', 'Catatan penolakan wajib diisi agar kandidat mengetahui alasannya.')
                    ->assertSeeIn('.prof .bd', 'Menunggu Keputusan');
                $this->assertSame($riwayatAwal, (int) $browser->script("return document.querySelectorAll('.tl .it').length;")[0]);
                $this->bukti($browser, "TC_PBI20_003_{$variasi}");
            }
        });
    }
}
