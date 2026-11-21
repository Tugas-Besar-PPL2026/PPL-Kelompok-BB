<?php

namespace Tests\Browser\Habib\PBI04;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI04-009 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat tidak dapat mengubah pengalaman kerja milik kandidat lain.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Kandidat B mengirim aksi ubah (PUT) dan hapus (DELETE) untuk ID pengalaman milik A dari browser (sesi + CSRF asli,
 * seperti memanipulasi form lewat DevTools), serta membuka /kandidat/profil?ubah_pengalaman={id A}.
 * Aplikasi memakai Gate ExperiencePolicy -> 403.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-009')]
class TC_PBI04_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_009_kandidat_lain_tidak_dapat_mengubah_pengalaman(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidatA = $this->siapkanKandidatProfil($browser, 'Taufik Hidayat', ['pengalaman' => [[self::BIDANG, self::DURASI, self::HASIL]]]);
            $kandidatB = $this->siapkanKandidatProfil($browser, 'Anisa Fitriani');
            $expA = $this->pengalamanDb($kandidatA['email'])[0];

            // Langkah 1: sebagai kandidat B, buka URL edit pengalaman A.
            $this->masukSebagaiKandidat($browser, $kandidatB);
            $browser->visit("/kandidat/profil?ubah_pengalaman={$expA->id}")->waitFor('#form-pengalaman');
            // Langkah 2: form tidak memuat data A (tetap mode tambah milik B).
            $browser->assertSeeIn('#exp-form-title', 'Tambah pengalaman')
                ->assertInputValue('#work_type', '')
                ->assertInputValue('#exp_description', '')
                ->assertSeeIn('#experience-list', self::MSG_EXP_KOSONG)
                ->assertDontSee(self::HASIL);

            // Aksi ubah pengalaman A -> ditolak.
            $this->kirimFormManipulasi($browser, 'PUT', "/kandidat/profil/pengalaman/{$expA->id}", [
                ['work_type', 'Diubah oleh kandidat B'], ['duration', '1 bulan'], ['description', 'Tidak sah'],
            ]);
            $this->assertHalamanGalat($browser, 403, 'This action is unauthorized');

            // Aksi hapus pengalaman A -> ditolak.
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->kirimFormManipulasi($browser, 'DELETE', "/kandidat/profil/pengalaman/{$expA->id}");
            $this->assertHalamanGalat($browser, 403, 'This action is unauthorized');
            $browser->visit('/kandidat/profil')->waitFor('#form-pengalaman');
            $this->keluar($browser);

            // Langkah 3 / Expected: data kandidat A tidak berubah.
            $sesudah = $this->pengalamanDb($kandidatA['email']);
            $this->assertCount(1, $sesudah);
            $this->assertEquals($expA, $sesudah[0]);
            $this->assertSame([], $this->pengalamanDb($kandidatB['email']));

            $this->masukSebagaiKandidat($browser, $kandidatA);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->assertSame([[self::BIDANG, self::DURASI, self::HASIL]], $this->pengalamanTampil($browser));
        });
    }
}
