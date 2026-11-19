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
 * TC-PBI15-004 (sheet PBI 15, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: katalog uji PBI-15 hari ini (A, B, C) dipublikasikan oleh UMKM "Kopi Nusantara PBI15"
 *              (dibuat lewat form dan disetujui Admin bila belum ada); satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, isi kode katalog, tenggat paling lambat hari ini + 4, Terapkan.
 *   Kata kunci kode katalog [K] membatasi hasil pada katalog uji, karena database test persistent
 *   juga berisi proyek terbuka dari test lain.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi15')]
#[Group('TC-PBI15-004')]
class TC_PBI15_004_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI15-004 - Filter tenggat lamaran menampilkan proyek dengan batas waktu sesuai pilihan.
     */
    public function test_TC_PBI15_004_filter_tenggat_lamaran(): void
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

            // Langkah 3-5: ketik kode katalog, pilih tenggat paling lambat hari ini + 4, klik Terapkan.
            // Input bertipe date diisi lewat nilai ISO agar tidak bergantung pada format tanggal locale Chrome.
            $tanggal = now()->addDays(4)->toDateString();
            $browser->type('@cari-proyek', $kode);
            $browser->driver->executeScript("document.getElementById('tenggat_sebelum').value = arguments[0];", [$tanggal]);
            $this->terapkanFilter($browser);

            $this->assertSame($tanggal, $this->queryHalaman($browser)['tenggat_sebelum'] ?? null);
            $this->assertSame([], $this->judulKartuEksplor($browser), 'Tidak ada proyek yang tampil.');
            $browser->assertSeeIn('@hasil-kosong', 'Tidak ada proyek yang cocok')
                ->assertSeeIn('@jumlah-hasil', '0 proyek ditemukan')
                ->assertSeeIn('@jumlah-hasil', '2 filter aktif');
        });
    }
}
