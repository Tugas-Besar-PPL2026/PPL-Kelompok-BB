<?php

namespace Tests\Browser\Aisyah\PBI13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI13-008 (sheet PBI 13, PBI 13 14 15.xlsx). Test case baru untuk FR-07 (alasan penolakan).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi Admin, lalu mengajukan satu proyek lewat form (Menunggu Moderasi).
 *   Langkah  : login Admin, Moderasi Proyek, Tolak dengan catatan; login UMKM, Proyek Saya,
 *              periksa kartu dan tab Ditolak.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-008')]
class TC_PBI13_008_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    private const CATATAN = 'Proyek tidak sesuai ketentuan layanan ProyekKita.';

    /**
     * TC-PBI13-008 - Proyek yang ditolak Admin tampil Ditolak beserta catatan penolakan tanpa tombol aksi.
     */
    public function test_TC_PBI13_008_proyek_ditolak_menampilkan_catatan(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi mengajukan satu proyek lewat form.
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $data = $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]);
            $this->masukSebagaiUmkm($browser, $akun);
            $proyekId = $this->buatDanAjukanProyek($browser, $data);
            $this->keluar($browser);

            // Langkah 1-3: login Admin, Moderasi Proyek, pilih proyek, Tolak dengan catatan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiLewatMenu($browser, $proyekId, $data['judul'], 'rejected', self::CATATAN);

            // Langkah 4-5: keluar, login UMKM, menu Proyek Saya.
            $this->keluar($browser);
            $this->masukSebagaiUmkm($browser, $akun);
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@status-proyek-{$proyekId}", 'Ditolak')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", 'Hasil moderasi terakhir: Ditolak')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", self::CATATAN)
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", $this->tanggalIndonesia())
                ->assertSeeIn("@kartu-proyek-{$proyekId}", 'Proyek ditolak dan tidak ditampilkan kepada kandidat.');

            $jumlahTombol = $browser->driver->executeScript(
                "return document.querySelectorAll('[dusk=\"kartu-proyek-' + arguments[0] + '\"] .pm-foot a').length;",
                [$proyekId]
            );
            $this->assertSame(0, $jumlahTombol, 'Kartu proyek Ditolak tidak memiliki tombol aksi.');
            $this->assertSame(
                ['dipublikasikan' => 0, 'menunggu' => 0, 'perbaikan' => 0, 'pelamar' => 0],
                $this->kartuRingkasan($browser)
            );

            // Langkah 6: tab Ditolak berangka 1 dan menampilkan proyek tersebut.
            $this->assertSame(1, $this->tabStatusProyek($browser)['Ditolak']);
            $this->klikTabStatus($browser, 'tab-rejected');
            $this->assertSame([$proyekId], $this->idKartuProyekSaya($browser));

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('rejected', $this->statusProyekDb($proyekId));
            $moderasi = DB::table('project_moderations')->where('project_id', $proyekId)->orderByDesc('id')->first();
            $this->assertSame('rejected', $moderasi->decision);
            $this->assertSame(self::CATATAN, $moderasi->note);
        });
    }
}
