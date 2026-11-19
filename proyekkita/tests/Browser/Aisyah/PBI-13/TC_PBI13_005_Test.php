<?php

namespace Tests\Browser\Aisyah\PBI13;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI13-005 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi Admin, lalu lewat form menyimpan satu draf dan mengajukan satu proyek.
 *   Langkah  : login UMKM, Proyek Saya, klik tab Draf, Menunggu Moderasi, Ditolak, Semua.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-005')]
class TC_PBI13_005_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI13-005 - Tab status menyaring daftar proyek sesuai status dan tab kosong menampilkan pesan.
     */
    public function test_TC_PBI13_005_tab_status_menyaring_proyek(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi dengan satu draf dan satu proyek Menunggu Moderasi (lewat form).
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $this->masukSebagaiUmkm($browser, $akun);
            $draf = $this->buatDrafProyek($browser, $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]));
            $diajukan = $this->buatDanAjukanProyek($browser, $this->dataProyek(['judul' => "Desain Tag Harga Baju {$akun['kode']}"]));
            $this->keluar($browser);

            // Langkah 1-2: login UMKM, menu Proyek Saya.
            $this->masukSebagaiUmkm($browser, $akun);
            $this->klikTautan($browser, 'Proyek Saya')->assertPathIs('/umkm/proyek');

            // Angka pada setiap tab sesuai jumlah proyek.
            $this->assertSame([
                'Semua' => 2,
                'Dipublikasikan' => 0,
                'Menunggu Moderasi' => 1,
                'Perlu Perbaikan' => 0,
                'Draf' => 1,
                'Ditolak' => 0,
                'Ditutup' => 0,
            ], $this->tabStatusProyek($browser));

            // Langkah 3: tab Draf hanya menampilkan proyek draf.
            $this->klikTabStatus($browser, 'tab-draft')->assertAttribute('@tab-draft', 'aria-current', 'page');
            $this->assertSame('draft', $this->queryHalaman($browser)['status'] ?? null);
            $this->assertSame([$draf], $this->idKartuProyekSaya($browser));
            $browser->assertSeeIn("@status-proyek-{$draf}", 'Draf');

            // Langkah 4: tab Menunggu Moderasi hanya menampilkan proyek yang diajukan.
            $this->klikTabStatus($browser, 'tab-pending_review');
            $this->assertSame('pending_review', $this->queryHalaman($browser)['status'] ?? null);
            $this->assertSame([$diajukan], $this->idKartuProyekSaya($browser));
            $browser->assertSeeIn("@status-proyek-{$diajukan}", 'Menunggu Moderasi');

            // Langkah 5: tab Ditolak kosong dan menampilkan pesan.
            $this->klikTabStatus($browser, 'tab-rejected')
                ->assertSeeIn('@tab-kosong', 'Tidak ada proyek dengan status Ditolak');
            $this->assertSame([], $this->idKartuProyekSaya($browser));

            // Langkah 6: tab Semua menampilkan kedua proyek.
            $this->klikTabStatus($browser, 'tab-semua')->assertPathIs('/umkm/proyek');
            $this->assertArrayNotHasKey('status', $this->queryHalaman($browser));
            $this->assertSame([$diajukan, $draf], $this->idKartuProyekSaya($browser), 'Proyek terbaru tampil lebih dulu.');

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame(1, $this->jumlahProyekUmkmDb($akun['email'], 'draft'));
            $this->assertSame(1, $this->jumlahProyekUmkmDb($akun['email'], 'pending_review'));
        });
    }
}
