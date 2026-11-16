<?php

namespace Tests\Browser\Cerin\PBI19;

use Laravel\Dusk\Browser;
use Tests\Browser\Cerin\CerinDuskTestCase;

/**
 * TC-PBI19-013 — Dokumen portofolio kandidat lain tidak dapat dibuka lewat lamaran yang berbeda
 *
 * Jenis Test     : Negatif
 * Data disiapkan : 1 proyek terbit; K1 melamar; K2 mengunggah cv.pdf sebagai portofolio lalu melamar.
 *                  Seluruh data dibuat lewat UI browser oleh test ini sendiri (tanpa factory/seeder/SQL).
 * Expected       : Sistem menjawab 404.
 *
 * Jalankan       : php artisan dusk tests/Browser/Cerin/PBI-19/TC_PBI19_013_Test.php
 */
class TC_PBI19_013_Test extends CerinDuskTestCase
{
    public function test_tc_pbi19_013(): void
    {
        $this->browse(function (Browser $browser) {
            $umkm = $this->siapkanUmkmTerverifikasi($browser, 'UMKM A');
            $proyek = $this->siapkanProyekTerbit($browser, $umkm, 'Proyek', 2);
            $k1 = $this->daftarKandidat($browser, 'K1');
            $this->lamar($browser, $k1, $proyek);
            $k2 = $this->daftarKandidat($browser, 'K2', ['portofolio' => [['judul' => 'CV Kandidat 2', 'berkas' => true]]]);
            $this->lamar($browser, $k2, $proyek);

            // ===== Skenario utama =====
            $this->masuk($browser, $umkm);
            $this->bukaSeleksi($browser);
            $this->pilihProyek($browser, $proyek['id']);
            $lamaranA = $this->lamaranIdDiTabel($browser, $k1['nama']);

            // ID portofolio B dibaca dari tautan dokumen pada detail pelamar K2
            $this->lihatPelamar($browser, $this->lamaranIdDiTabel($browser, $k2['nama']));
            $href = collect($this->kartuPortofolio($browser, 'CV Kandidat 2')['tautan'])->first(fn ($h) => str_contains((string) $h, '/portofolio/'));
            $this->assertNotNull($href);
            preg_match('#/portofolio/(\d+)#', $href, $m);
            $portofolioB = (int) $m[1];

            $browser->visit("/umkm/seleksi/pelamar/{$lamaranA}/portofolio/{$portofolioB}")
                ->assertSee('404');
            $this->bukti($browser, 'TC_PBI19_013');
        });
    }
}
