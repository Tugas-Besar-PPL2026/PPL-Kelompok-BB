<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-002 — Memasukkan pelamar ke Terpilih Sementara dengan catatan
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar (Menunggu Keputusan).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Status Terpilih Sementara; catatan tersimpan pada lamaran dan riwayat status.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_002_Test.php
 */
class TC_PBI20_002_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_002(): void
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

            $this->pilihKeputusan($browser, 'shortlist');
            $this->isiCatatan($browser, 'Portofolio kuat');
            $this->simpanKeputusan($browser);

            $browser->assertSeeIn('.prof .bd', 'Terpilih Sementara')
                ->assertSeeIn('.tl', 'Catatan: Portofolio kuat');
            $this->assertStringContainsString('Catatan seleksi tersimpan: Portofolio kuat', $this->teksRapi($browser, '[dusk="panel-keputusan"]'));
            $this->bukti($browser, 'TC_PBI20_002');
        });
    }
}
