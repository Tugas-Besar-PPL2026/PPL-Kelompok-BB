<?php

namespace Tests\Browser\Aisyah\PBI15;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI15-005 (sheet PBI 15, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: katalog uji PBI-15 hari ini (A, B, C) dipublikasikan oleh UMKM "Kopi Nusantara PBI15"
 *              (dibuat lewat form dan disetujui Admin bila belum ada); satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, isi kode katalog, pilih Urutkan Kompensasi tertinggi, Terapkan.
 *   Kata kunci kode katalog [K] membatasi hasil pada katalog uji, karena database test persistent
 *   juga berisi proyek terbuka dari test lain.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi15')]
#[Group('TC-PBI15-005')]
class TC_PBI15_005_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI15-005 - Daftar proyek diurutkan berdasarkan kompensasi tertinggi.
     */
    public function test_TC_PBI15_005_urutkan_kompensasi_tertinggi(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: katalog uji hari ini dan akun kandidat.
            $kode = $this->pastikanKatalogPbi15($browser);
            $judul = $this->judulKatalogPbi15($kode);
            $kandidat = $this->daftarKandidatBaru($browser, 'Kandidat PBI15');
            $this->keluar($browser);

            // Langkah 1-2: login kandidat, menu Eksplor Proyek.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaEksplor($browser);

            // Langkah 3-5: ketik kode katalog, pilih Kompensasi tertinggi, klik Terapkan.
            $browser->type('@cari-proyek', $kode);
            (new WebDriverSelect($browser->driver->findElement(WebDriverBy::id('urut'))))->selectByVisibleText('Kompensasi tertinggi');
            $this->terapkanFilter($browser);

            $this->assertSame('kompensasi_tertinggi', $this->queryHalaman($browser)['urut'] ?? null);
            $this->assertSame([$judul['c'], $judul['a'], $judul['b']], $this->judulKartuEksplor($browser), 'Urutan C, A, B.');
            $this->assertStringContainsString('Rp3.000.000', $this->teksKartuEksplor($browser, $judul['c']));
            $this->assertStringContainsString('Rp1.500.000', $this->teksKartuEksplor($browser, $judul['a']));
            $this->assertStringContainsString('Rp800.000', $this->teksKartuEksplor($browser, $judul['b']));
            $browser->assertSeeIn('@jumlah-hasil', '3 proyek ditemukan')
                ->assertSeeIn('@jumlah-hasil', '1 filter aktif');
        });
    }
}
