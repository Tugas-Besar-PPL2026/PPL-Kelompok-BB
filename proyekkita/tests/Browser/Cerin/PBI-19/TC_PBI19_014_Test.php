<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-014 — Detail kandidat tanpa pengalaman, portofolio, rating, dan riwayat
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K3 hanya mengisi Data Diri dan satu keterampilan (syarat melamar), lalu melamar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Tampil 'Kandidat belum menambahkan pengalaman kerja.', 'Kandidat belum menambahkan portofolio.', dan 'Belum ada penilaian · 0 proyek selesai'. Halaman tidak error.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_014_Test.php
 */
class TC_PBI19_014_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_014(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k3 = $this->daftarKandidat($browser, 'K3');
            $this->lamar($browser, $k3, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k3['nama']));

            $browser->assertSee('Kandidat belum menambahkan pengalaman kerja.')
                ->assertSee('Kandidat belum menambahkan portofolio.')
                ->assertSee('Belum ada penilaian · 0 proyek selesai')
                ->assertDontSee('Server Error');
            $this->bukti($browser, 'TC_PBI19_014');
        });
    }
}
