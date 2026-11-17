<?php

namespace Tests\Browser\Cerin\PBI21;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI21-011 — Opsi Terima dinonaktifkan pada detail saat kuota penuh
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit kuota 1; K1 dan K2 melamar; K1 diterima sehingga kuota penuh.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Opsi 'Terima pelamar' tidak bisa dipilih dan menampilkan 'Kuota sudah terpenuhi, pelamar tidak dapat diterima lagi.'
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-21/TC_PBI21_011_Test.php
 */
class TC_PBI21_011_Test extends CerinDuskTestCase
{
    public function test_tc_pbi21_011(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 1);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $this->putuskan($browser, $umkm, $proyek, $k1, 'accept');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k2['nama']));

            $browser->assertAttribute('@opsi-accept input', 'disabled', 'true')
                ->assertSeeIn('@opsi-accept', 'Kuota sudah terpenuhi, pelamar tidak dapat diterima lagi.')
                ->assertSeeIn('.prof .bd', 'Menunggu Keputusan');
            $this->bukti($browser, 'TC_PBI21_011');
        });
    }
}
