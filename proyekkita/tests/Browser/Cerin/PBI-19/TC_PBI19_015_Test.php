<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-015 — UMKM membuka detail lamaran milik UMKM lain
 *
 * Jenis Test     : Negatif
 * Data disiapkan : UMKM B terverifikasi dengan 1 proyek terbit yang dilamar K1; UMKM A didaftarkan.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Akses ditolak (403).
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_015_Test.php
 */
class TC_PBI19_015_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_015(): void
    {
        $this->browse(function (Browser $browser) {
            $umkmB = $this->siapkanUmkmTerverifikasi($browser, 'UMKM B');
            $proyekB = $this->siapkanProyekTerbit($browser, $umkmB, 'Proyek B', 1);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyekB);
            $lamaranLain = $this->persiapan('baca ID lamaran milik UMKM B', function () use ($browser, $umkmB, $proyekB, $k1) {
                $this->masuk($browser, $umkmB);
                $this->bukaSeleksi($browser);
                $this->pilihProyek($browser, $proyekB['id']);
                $id = $this->lamaranIdDiTabel($browser, $k1['nama']);
                $this->keluar($browser);

                return $id;
            });
            $umkm = $this->daftarUmkm($browser, 'UMKM A');

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $browser->visit("/umkm/seleksi/pelamar/{$lamaranLain}")
                ->assertSee('403')
                ->assertMissing('@panel-keputusan');
            $this->bukti($browser, 'TC_PBI19_015');
        });
    }
}
