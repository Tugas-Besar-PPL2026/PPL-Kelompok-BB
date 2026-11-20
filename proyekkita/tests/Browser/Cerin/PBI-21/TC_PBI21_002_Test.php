<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-002 — Menerima pelamar Terpilih Sementara tanpa catatan
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit kuota 2; K1 melamar lalu dimasukkan ke Terpilih Sementara.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status menjadi Diterima tanpa kesalahan validasi.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_002_Test.php
 */
class TC_PBI21_002_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_002(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'shortlist');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaran = $this->lamaranIdDiTabel($browser, $k1['nama']);
            $this->lihatPelamar($browser, $lamaran);

            $this->pilihKeputusan($browser, 'accept');
            $this->isiCatatan($browser, '');
            $this->simpanKeputusan($browser);

            $browser->assertMissing('@galat-keputusan')
                ->assertSee('Pelamar berhasil diterima.')
                ->assertSeeIn('.prof .bd', 'Diterima');
            $this->bukti($browser, 'TC_PBI21_002');
        });
    }
}
