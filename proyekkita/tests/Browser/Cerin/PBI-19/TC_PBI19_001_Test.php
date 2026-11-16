<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-001 — Melihat daftar pelamar beserta statistik dan informasi kuota
 *
 * Jenis Test     : Positif
 * Data disiapkan : UMKM A terverifikasi; 1 proyek terbit kuota 2; 5 kandidat melamar; UMKM: K1 Terpilih Sementara, K4 Diterima, K5 Ditolak (K2, K3 Menunggu).
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Kartu statistik: Total 5, Menunggu 2, Terpilih sementara 1, Diterima 1 / 2. Tab Ditolak 1. 'Kuota kandidat terisi 1 dari 2' dan 'Sisa 1 kandidat dapat diterima'.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_001_Test.php
 */
class TC_PBI19_001_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_001(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $k3 = $this->daftarKandidat($browser, 'K3');
            $this->lamar($browser, $k3, $proyek);
            $k4 = $this->daftarKandidat($browser, 'K4');
            $this->lamar($browser, $k4, $proyek);
            $k5 = $this->daftarKandidat($browser, 'K5');
            $this->lamar($browser, $k5, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'shortlist');
            $this->putuskan($browser, $umkm, $proyek, $k4, 'accept');
            $this->putuskan($browser, $umkm, $proyek, $k5, 'reject', 'Catatan penolakan persiapan data');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            $this->assertSame(['5', '2', '1', '1 / 2'], $this->statistik($browser));
            $this->assertContains('Ditolak 1', $this->tabStatus($browser));
            $browser->assertSeeIn('@kuota-daftar', 'Kuota kandidat terisi 1 dari 2')
                ->assertSee('Sisa 1 kandidat dapat diterima');
            $this->assertCount(5, $this->namaDiTabel($browser));
            $this->bukti($browser, 'TC_PBI19_001');
        });
    }
}
