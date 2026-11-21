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
 * TC-PBI04-005 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Pengalaman kerja tidak dapat disimpan ketika data yang diwajibkan kosong.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Field wajib sesuai CandidateExperienceRequest: work_type dan duration (required). Diuji: seluruh field wajib
 * kosong, lalu hanya Durasi yang kosong.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-005')]
class TC_PBI04_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_005_validasi_field_wajib_pengalaman(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Ilham Ramadhan');

            // Langkah 1: buka form tambah pengalaman.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#exp-form-title', 'Tambah pengalaman');

            // Langkah 2-4 (a): kosongkan seluruh field wajib, simpan, amati validasi.
            $this->isiFormPengalaman($browser, '', '', 'Deskripsi tanpa bidang dan durasi.');
            $this->simpanPengalaman($browser)->assertDontSee(self::MSG_EXP_DITAMBAH);
            $this->assertSame('Bidang / jenis pekerjaan wajib diisi.', $this->galatField($browser, 'work_type'));
            $this->assertSame('Durasi wajib diisi.', $this->galatField($browser, 'duration'));
            $browser->assertInputValue('#exp_description', 'Deskripsi tanpa bidang dan durasi.');

            // (b): hanya Durasi yang kosong.
            $this->isiFormPengalaman($browser, self::BIDANG, '', self::HASIL);
            $this->simpanPengalaman($browser)->assertDontSee(self::MSG_EXP_DITAMBAH);
            $this->assertNull($this->galatField($browser, 'work_type'));
            $this->assertSame('Durasi wajib diisi.', $this->galatField($browser, 'duration'));
            $browser->assertInputValue('#work_type', self::BIDANG);

            // Expected: tidak ada pengalaman baru yang tersimpan.
            $browser->assertSeeIn('#experience-list', self::MSG_EXP_KOSONG);
            $this->assertSame([], $this->pengalamanDb($kandidat['email']));
        });
    }
}
