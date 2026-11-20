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
 * TC-PBI13-003 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi Admin, lalu UMKM menyimpan satu draf lewat form.
 *   Langkah  : login UMKM, Proyek Saya, tab Draf, Lanjutkan Draf, Tinjau & Ajukan,
 *              centang pernyataan, Ajukan untuk Moderasi, kembali ke Proyek Saya.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-003')]
class TC_PBI13_003_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI13-003 - Proyek yang diajukan ke moderasi tampil Menunggu Moderasi tanpa tombol aksi.
     */
    public function test_TC_PBI13_003_proyek_diajukan_tampil_menunggu_moderasi(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi dengan satu draf yang dibuat lewat form.
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $data = $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]);
            $this->masukSebagaiUmkm($browser, $akun);
            $proyekId = $this->buatDrafProyek($browser, $data);
            $this->keluar($browser);

            // Langkah 1: login UMKM.
            $this->masukSebagaiUmkm($browser, $akun);

            // Langkah 2: Proyek Saya, buka tab Draf, klik Lanjutkan Draf.
            $this->klikTautan($browser, 'Proyek Saya')->assertPathIs('/umkm/proyek');
            $this->klikTabStatus($browser, 'tab-draft')
                ->assertSeeIn("@kartu-proyek-{$proyekId}", $data['judul']);
            $this->tandaiHalamanLama($browser);
            $browser->click("@lanjutkan-draf-{$proyekId}");
            $this->tungguHalamanBaru($browser)->assertPathIs("/umkm/proyek/{$proyekId}/ubah");

            // Langkah 3-4: Tinjau & Ajukan, centang pernyataan, Ajukan untuk Moderasi.
            $this->assertSame($proyekId, $this->tinjauDanAjukan($browser));
            $browser->assertSee(self::MSG_DIAJUKAN);

            // Langkah 5: menu Proyek Saya.
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@status-proyek-{$proyekId}", 'Menunggu Moderasi')
                ->assertSeeIn("@kartu-proyek-{$proyekId}", 'Admin sedang memeriksa proyek ini.')
                ->assertMissing("@lanjutkan-draf-{$proyekId}")
                ->assertMissing("@perbaiki-{$proyekId}")
                ->assertMissing("@lihat-pelamar-proyek-{$proyekId}")
                ->assertMissing("@catatan-moderasi-{$proyekId}");

            $jumlahTombol = $browser->driver->executeScript(
                "return document.querySelectorAll('[dusk=\"kartu-proyek-' + arguments[0] + '\"] .pm-foot a').length;",
                [$proyekId]
            );
            $this->assertSame(0, $jumlahTombol, 'Kartu proyek Menunggu Moderasi tidak memiliki tombol aksi.');
            $this->assertSame(
                ['dipublikasikan' => 0, 'menunggu' => 1, 'perbaikan' => 0, 'pelamar' => 0],
                $this->kartuRingkasan($browser)
            );

            // Tab Menunggu Moderasi menampilkan proyek tersebut.
            $this->klikTabStatus($browser, 'tab-pending_review');
            $this->assertSame('pending_review', $this->queryHalaman($browser)['status'] ?? null);
            $this->assertSame([$proyekId], $this->idKartuProyekSaya($browser));

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('pending_review', $this->statusProyekDb($proyekId));
        });
    }
}
