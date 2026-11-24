<?php

namespace Tests\Browser\Cerin\PBI24;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\KesepakatanDuskTestCase;

/**
 * TC-PBI24-014 — Lamaran belum diterima
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; kandidat kedua melamar lalu dimasukkan ke Terpilih Sementara.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tombol Susun Kesepakatan tidak tampil pada detail pelamar; tidak ada kesepakatan di tab Draf.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-24/TC_PBI24_014_Test.php
 */
class TC_PBI24_014_Test extends KesepakatanDuskTestCase
{
    public function test_tc_pbi24_014(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2, true);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $lamaran = $this->putuskan($browser, $umkm, $proyek, $k2, 'shortlist');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $lamaran);
            $browser->assertSeeIn('.prof .bd', 'Terpilih Sementara')
                ->assertMissing('@susun-kesepakatan')
                ->assertMissing('@panel-kesepakatan');
            $this->bukti($browser, 'TC_PBI24_014');

            $this->bukaMenuKesepakatan($browser, 'umkm');
            $this->assertSame(0, $this->jumlahTab($browser, 'draft'));
        });
    }
}
