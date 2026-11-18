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
 * TC-PBI13-004 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi Admin, lalu mengajukan satu proyek lewat form (Menunggu Moderasi).
 *   Langkah  : login Admin, Moderasi Proyek, pilih proyek, Setujui dengan catatan, Simpan Keputusan, keluar;
 *              login UMKM, Proyek Saya, periksa kartu, klik Lihat Pelamar.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-004')]
class TC_PBI13_004_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    private const CATATAN = 'Informasi proyek lengkap.';

    /**
     * TC-PBI13-004 - Proyek yang disetujui Admin tampil Dipublikasikan beserta catatan moderasi dan tombol Lihat Pelamar.
     */
    public function test_TC_PBI13_004_proyek_disetujui_tampil_dipublikasikan(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi mengajukan satu proyek lewat form.
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $data = $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]);
            $this->masukSebagaiUmkm($browser, $akun);
            $proyekId = $this->buatDanAjukanProyek($browser, $data);
            $this->keluar($browser);

            // Langkah 1: login Admin.
            $this->masukSebagaiAdmin($browser);

            // Langkah 2-3: menu Moderasi Proyek, pilih proyek, Setujui dengan catatan, Simpan Keputusan.
            $this->moderasiLewatMenu($browser, $proyekId, $data['judul'], 'approved', self::CATATAN)
                ->assertSee(self::MSG_MODERASI_DISIMPAN);

            // Langkah 4: keluar, login UMKM.
            $this->keluar($browser);
            $this->masukSebagaiUmkm($browser, $akun);

            // Langkah 5: menu Proyek Saya.
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@status-proyek-{$proyekId}", 'Dipublikasikan')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", 'Hasil moderasi terakhir: Disetujui')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", self::CATATAN)
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", $this->tanggalIndonesia())
                ->assertSeeIn("@lihat-pelamar-proyek-{$proyekId}", 'Lihat Pelamar');

            $this->assertSame(
                ['dipublikasikan' => 1, 'menunggu' => 0, 'perbaikan' => 0, 'pelamar' => 0],
                $this->kartuRingkasan($browser)
            );

            // Langkah 6: klik Lihat Pelamar, membuka Seleksi Kandidat untuk proyek tersebut.
            $this->tandaiHalamanLama($browser);
            $browser->click("@lihat-pelamar-proyek-{$proyekId}");
            $this->tungguHalamanBaru($browser)
                ->assertPathIs('/umkm/seleksi')
                ->assertSee('Seleksi Kandidat')
                ->assertValue('#pilih-proyek', (string) $proyekId);
            $this->assertSame((string) $proyekId, $this->queryHalaman($browser)['proyek'] ?? null);

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('published', $this->statusProyekDb($proyekId));
            $moderasi = DB::table('project_moderations')->where('project_id', $proyekId)->orderByDesc('id')->first();
            $this->assertSame('approved', $moderasi->decision);
            $this->assertSame(self::CATATAN, $moderasi->note);
        });
    }
}
