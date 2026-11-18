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
 * TC-PBI15-002 (sheet PBI 15, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: katalog uji PBI-15 hari ini (A, B, C) dipublikasikan oleh UMKM "Kopi Nusantara PBI15"
 *              (dibuat lewat form dan disetujui Admin bila belum ada); satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, isi kode katalog, pilih Kategori, Terapkan.
 *   Kata kunci kode katalog [K] membatasi hasil pada katalog uji, karena database test persistent
 *   juga berisi proyek terbuka dari test lain.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi15')]
#[Group('TC-PBI15-002')]
class TC_PBI15_002_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI15-002 - Filter kategori hanya menampilkan proyek pada kategori terpilih.
     */
    public function test_TC_PBI15_002_filter_kategori(): void
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

            // Langkah 3-5: ketik kode katalog, pilih kategori, klik Terapkan.
            $browser->type('@cari-proyek', $kode);
            (new WebDriverSelect($browser->driver->findElement(WebDriverBy::id('kategori'))))->selectByVisibleText(self::KAT_DESAIN);
            $this->terapkanFilter($browser);

            $this->assertSame((string) $this->idKategori(self::KAT_DESAIN), $this->queryHalaman($browser)['kategori'] ?? null);
            $this->assertEqualsCanonicalizing([$judul['a'], $judul['c']], $this->judulKartuEksplor($browser), 'Proyek A dan C tampil, B tidak.');
            $browser->assertSeeIn('@jumlah-hasil', '2 proyek ditemukan')
                ->assertSeeIn('@jumlah-hasil', '2 filter aktif');
        });
    }
}
