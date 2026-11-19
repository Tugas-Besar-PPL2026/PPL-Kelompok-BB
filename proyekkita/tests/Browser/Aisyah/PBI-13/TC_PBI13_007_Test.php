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
 * TC-PBI13-007 (sheet PBI 13, PBI 13 14 15.xlsx). Test case baru untuk FR-07 (catatan perbaikan).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi Admin, lalu mengajukan satu proyek lewat form (Menunggu Moderasi).
 *   Langkah  : login Admin, Moderasi Proyek, Minta Perbaikan dengan catatan; login UMKM, Proyek Saya,
 *              periksa kartu, tab Perlu Perbaikan, klik Perbaiki & Ajukan Ulang.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-007')]
class TC_PBI13_007_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    private const CATATAN = 'Lengkapi jumlah varian logo dan ukuran file yang diharapkan.';

    /**
     * TC-PBI13-007 - Proyek yang diminta perbaikan tampil Perlu Perbaikan beserta catatan Admin dan tombol Perbaiki & Ajukan Ulang.
     */
    public function test_TC_PBI13_007_proyek_perlu_perbaikan_menampilkan_catatan(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi mengajukan satu proyek lewat form.
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $data = $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]);
            $this->masukSebagaiUmkm($browser, $akun);
            $proyekId = $this->buatDanAjukanProyek($browser, $data);
            $this->keluar($browser);

            // Langkah 1-3: login Admin, Moderasi Proyek, pilih proyek, Minta Perbaikan dengan catatan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiLewatMenu($browser, $proyekId, $data['judul'], 'revision_requested', self::CATATAN);

            // Langkah 4-5: keluar, login UMKM, menu Proyek Saya.
            $this->keluar($browser);
            $this->masukSebagaiUmkm($browser, $akun);
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@status-proyek-{$proyekId}", 'Perlu Perbaikan')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", 'Hasil moderasi terakhir: Minta Perbaikan')
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", self::CATATAN)
                ->assertSeeIn("@catatan-moderasi-{$proyekId}", $this->tanggalIndonesia())
                ->assertSeeIn("@perbaiki-{$proyekId}", 'Perbaiki & Ajukan Ulang')
                ->assertMissing("@lihat-pelamar-proyek-{$proyekId}");

            $this->assertSame(
                ['dipublikasikan' => 0, 'menunggu' => 0, 'perbaikan' => 1, 'pelamar' => 0],
                $this->kartuRingkasan($browser)
            );

            // Langkah 6: tab Perlu Perbaikan menampilkan proyek tersebut.
            $this->klikTabStatus($browser, 'tab-revision_requested');
            $this->assertSame([$proyekId], $this->idKartuProyekSaya($browser));

            // Langkah 7: Perbaiki & Ajukan Ulang membuka form proyek dengan isian tersimpan.
            $this->tandaiHalamanLama($browser);
            $browser->click("@perbaiki-{$proyekId}");
            $this->tungguHalamanBaru($browser)
                ->assertPathIs("/umkm/proyek/{$proyekId}/ubah")
                ->assertInputValue('title', $data['judul']);

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('revision_requested', $this->statusProyekDb($proyekId));
            $moderasi = DB::table('project_moderations')->where('project_id', $proyekId)->orderByDesc('id')->first();
            $this->assertSame('revision_requested', $moderasi->decision);
            $this->assertSame(self::CATATAN, $moderasi->note);
        });
    }
}
