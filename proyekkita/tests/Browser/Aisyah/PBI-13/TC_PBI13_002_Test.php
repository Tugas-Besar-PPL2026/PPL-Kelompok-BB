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
 * TC-PBI13-002 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: master data tersedia (dibuat Admin lewat Data Master bila belum ada),
 *              UMKM baru terdaftar dengan profil usaha terisi (belum diverifikasi).
 *   Langkah  : login UMKM, Proyek Saya, Buat Proyek, isi form, Simpan sebagai Draf,
 *              lalu periksa kartu di Proyek Saya dan tombol Lanjutkan Draf.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-002')]
class TC_PBI13_002_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI13-002 - Draf proyek yang disimpan lewat form tampil berstatus Draf dengan tombol Lanjutkan Draf.
     */
    public function test_TC_PBI13_002_draf_tampil_di_proyek_saya(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM baru dengan profil usaha terisi (tanpa verifikasi).
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo(), false);
            $data = $this->dataProyek(['judul' => "Redesain Logo Baju {$akun['kode']}"]);

            // Langkah 1: login UMKM lewat halaman /login.
            $this->masukSebagaiUmkm($browser, $akun);

            // Langkah 2-4: Proyek Saya, Buat Proyek, isi form, Simpan sebagai Draf.
            $proyekId = $this->buatDrafProyek($browser, $data);
            $browser->assertSee(self::MSG_DRAF_DISIMPAN);

            // Periksa kartu di Proyek Saya.
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@kartu-proyek-{$proyekId}", $data['judul'])
                ->assertSeeIn("@kartu-proyek-{$proyekId}", self::KAT_DESAIN)
                ->assertSeeIn("@status-proyek-{$proyekId}", 'Draf')
                ->assertSeeIn("@kartu-proyek-{$proyekId}", 'Rp2.500.000')
                ->assertSeeIn("@kartu-proyek-{$proyekId}", '2 orang')
                ->assertSeeIn("@jumlah-pelamar-{$proyekId}", '0')
                ->assertSeeIn("@lanjutkan-draf-{$proyekId}", 'Lanjutkan Draf')
                ->assertMissing("@catatan-moderasi-{$proyekId}");

            $this->assertSame([$proyekId], $this->idKartuProyekSaya($browser), 'Tepat satu kartu proyek.');
            $this->assertSame(
                ['dipublikasikan' => 0, 'menunggu' => 0, 'perbaikan' => 0, 'pelamar' => 0],
                $this->kartuRingkasan($browser),
                'Kartu Menunggu Moderasi dan Perlu Perbaikan tetap 0.'
            );

            // Tombol Lanjutkan Draf membuka form dengan isian tersimpan.
            $this->tandaiHalamanLama($browser);
            $browser->click("@lanjutkan-draf-{$proyekId}");
            $this->tungguHalamanBaru($browser)
                ->assertPathIs("/umkm/proyek/{$proyekId}/ubah")
                ->assertInputValue('title', $data['judul'])
                ->assertInputValue('description', $data['deskripsi'])
                ->assertInputValue('expected_output', $data['hasil'])
                ->assertInputValue('quota', '2')
                ->assertInputValue('total_compensation', '2500000')
                ->assertInputValue('terms[0][condition]', $data['termin'][0][1]);

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('draft', $this->statusProyekDb($proyekId));
            $this->assertSame(1, $this->jumlahProyekUmkmDb($akun['email'], 'draft'));
        });
    }
}
