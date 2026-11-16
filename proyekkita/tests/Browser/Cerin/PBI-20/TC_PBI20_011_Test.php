<?php

namespace Tests\Browser\Cerin\PBI20;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI20-011 — Kandidat melihat status dan catatan keputusan pada Lamaran Saya
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1 melamar lalu ditolak UMKM dengan catatan 'Belum sesuai kebutuhan'.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kartu lamaran berstatus 'Ditolak' dan menampilkan 'Catatan seleksi: Belum sesuai kebutuhan'. Timeline riwayat menampilkan status Ditolak beserta catatannya.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-20/TC_PBI20_011_Test.php
 */
class TC_PBI20_011_Test extends CerinDuskTestCase
{
    public function test_tc_pbi20_011(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'reject', 'Belum sesuai kebutuhan');

            // ===== Skenario utama =====
            $this->masuk($browser, $k1);
            $this->bukaLamaranSaya($browser);
            $kartu = '#lamaran-'.$this->lamaranIdDiLamaranSaya($browser, $proyek['judul']);

            $browser->assertSeeIn("{$kartu} .la-meta .bd", 'Ditolak')
                ->assertSeeIn("{$kartu} .la-note", 'Catatan seleksi: Belum sesuai kebutuhan')
                ->click("{$kartu} summary")
                ->waitFor("{$kartu} .tl", 20)
                ->assertSeeIn("{$kartu} .tl", 'Ditolak')
                ->assertSeeIn("{$kartu} .tl", 'Catatan UMKM: Belum sesuai kebutuhan');
            $this->bukti($browser, 'TC_PBI20_011');
        });
    }
}
