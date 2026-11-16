<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-007 — Keputusan Terpilih Sementara dan Tolak tidak menggunakan kuota
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 1; K1 dan K2 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Jumlah pelamar berstatus Diterima tetap 0.
 *
 * Catatan        : Excel tidak menyebut isi catatan penolakan; test memakai 'Catatan penolakan TC-PBI20-007' (Needs Review).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_007_Test.php
 */
class TC_PBI20_007_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_007(): void
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

            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k1['nama']));
            $this->pilihKeputusan($browser, 'shortlist');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar dimasukkan ke daftar terpilih sementara.');
            $this->kembaliKeDaftar($browser);

            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k2['nama']));
            $this->pilihKeputusan($browser, 'reject');
            $this->isiCatatan($browser, 'Catatan penolakan TC-PBI20-007');
            $this->simpanKeputusan($browser);
            $browser->assertSee('Pelamar ditolak dan catatan penolakan tersimpan.');
            $this->kembaliKeDaftar($browser);

            $this->assertSame('0 / 1', $this->statistik($browser)[3]);
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 0 dari 1');
            $this->bukti($browser, 'TC_PBI20_007');
        });
    }
}
