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
 * TC-PBI04-008 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Profil kandidat dapat ditampilkan ketika belum memiliki pengalaman kerja.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).

 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-008')]
class TC_PBI04_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_008_profil_tanpa_pengalaman_tetap_dapat_diakses(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Larasati Ningrum');

            // Langkah 1-2: buka profil, amati bagian Pengalaman (empty state).
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#experience-list', self::MSG_EXP_KOSONG)
                ->assertSeeIn('#exp-total-duration', '0 pengalaman')
                ->assertMissing('#experience-list .exp-item');
            $this->assertStringNotContainsString(' on ', ' '.$browser->attribute('[data-cek="pengalaman"]', 'class').' ');

            // Langkah 3: buka form tambah pengalaman.
            $browser->assertSeeIn('#exp-form-title', 'Tambah pengalaman')
                ->assertVisible('#work_type')->assertVisible('#duration')->assertVisible('#exp_description');

            // Expected: pengalaman tetap dapat ditambahkan.
            $this->isiFormPengalaman($browser, self::BIDANG, self::DURASI, self::HASIL);
            $this->simpanPengalaman($browser)->assertSee(self::MSG_EXP_DITAMBAH);
            $browser->assertDontSeeIn('#experience-list', self::MSG_EXP_KOSONG);
            $this->assertCount(1, $this->pengalamanDb($kandidat['email']));
        });
    }
}
