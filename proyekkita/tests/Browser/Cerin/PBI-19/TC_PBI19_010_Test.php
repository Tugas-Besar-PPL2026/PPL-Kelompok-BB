<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-010 — Tombol Lihat membuka detail kandidat yang sesuai dengan baris yang diklik
 *
 * Jenis Test     : Positif
 * Data disiapkan : 1 proyek terbit; K1, K2, K3 melamar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Detail yang terbuka adalah milik pelamar yang diklik.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_010_Test.php
 */
class TC_PBI19_010_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_010(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 3);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2');
            $this->lamar($browser, $k2, $proyek);
            $k3 = $this->daftarKandidat($browser, 'K3');
            $this->lamar($browser, $k3, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);

            $namaKe3 = $this->namaDiTabel($browser)[2];
            $browser->click('table.t.pl tbody tr:nth-child(3) a.btn')
                ->waitForText('Detail Pelamar', 20)
                ->assertPathBeginsWith('/umkm/seleksi/pelamar/')
                ->assertSeeIn('.prof h2', $namaKe3);
            $this->bukti($browser, 'TC_PBI19_010');
        });
    }
}
