<?php

namespace Tests\Browser\Aisyah\PBI15;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI15-001 (sheet PBI 15, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: katalog uji PBI-15 hari ini (A, B, C) dipublikasikan oleh UMKM "Kopi Nusantara PBI15"
 *              (dibuat lewat form dan disetujui Admin bila belum ada); satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, isi Kata kunci, Terapkan.
 *   Kata kunci kode katalog [K] membatasi hasil pada katalog uji, karena database test persistent
 *   juga berisi proyek terbuka dari test lain.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi15')]
#[Group('TC-PBI15-001')]
class TC_PBI15_001_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI15-001 - Kata kunci mencocokkan judul proyek.
     */
    public function test_TC_PBI15_001_kata_kunci_mencocokkan_judul(): void
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

            // Langkah 3-4: ketik kata kunci, klik Terapkan.
            $kataKunci = "Logo Kedai Kopi {$kode}";
            $browser->type('@cari-proyek', $kataKunci);
            $this->terapkanFilter($browser)->assertInputValue('@cari-proyek', $kataKunci);

            $this->assertSame($kataKunci, $this->queryHalaman($browser)['cari'] ?? null);
            $this->assertSame([$judul['a']], $this->judulKartuEksplor($browser), 'Hanya proyek A yang tampil.');
            $browser->assertSeeIn('@jumlah-hasil', '1 proyek ditemukan')
                ->assertSeeIn('@jumlah-hasil', '1 filter aktif');
        });
    }
}
