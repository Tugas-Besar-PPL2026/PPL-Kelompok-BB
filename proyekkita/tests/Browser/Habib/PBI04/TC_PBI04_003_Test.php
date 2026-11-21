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
 * TC-PBI04-003 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil memperbarui informasi pengalaman kerja.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Ikon pensil (aria-label "Ubah {bidang}") mengisi form dengan data lama dan mengubahnya menjadi mode "Ubah pengalaman".
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-003')]
class TC_PBI04_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_003_kandidat_memperbarui_pengalaman_kerja(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Bagas Kurniawan', ['pengalaman' => [[self::BIDANG, self::DURASI, self::HASIL]]]);
            $idAwal = (int) $this->pengalamanDb($kandidat['email'])[0]->id;
            $hasilBaru = 'Membuat 10 materi promosi digital.';

            // Langkah 1-2: buka bagian Pengalaman, pilih pengalaman yang akan diedit.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->klikTombolIkon($browser, '#experience-list a[aria-label="Ubah '.self::BIDANG.'"]');
            $browser->waitForTextIn('#exp-form-title', 'Ubah pengalaman')
                ->assertInputValue('#work_type', self::BIDANG)
                ->assertInputValue('#duration', self::DURASI)
                ->assertInputValue('#exp_description', self::HASIL)
                ->assertSeeIn('#btn-submit-exp', 'Simpan Perubahan');

            // Langkah 3-4: ubah hasil pekerjaan lalu simpan.
            $browser->clear('#exp_description')->type('#exp_description', $hasilBaru);
            $this->simpanPengalaman($browser);

            // Langkah 5 / Expected: informasi diperbarui dan tersimpan (bukan entri baru).
            $browser->assertSee(self::MSG_EXP_DIUBAH)->assertSeeIn('#exp-form-title', 'Tambah pengalaman');
            $this->assertSame([[self::BIDANG, self::DURASI, $hasilBaru]], $this->pengalamanTampil($browser));
            $browser->assertDontSeeIn('#experience-list', self::HASIL);

            $db = $this->pengalamanDb($kandidat['email']);
            $this->assertCount(1, $db);
            $this->assertSame($idAwal, (int) $db[0]->id);
            $this->assertSame($hasilBaru, $db[0]->description);
        });
    }
}
