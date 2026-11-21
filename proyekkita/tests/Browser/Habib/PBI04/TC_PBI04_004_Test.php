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
 * TC-PBI04-004 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menghapus pengalaman kerja dari profil.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Ikon tempat sampah (aria-label "Hapus {bidang}"). Aplikasi tidak menampilkan dialog konfirmasi (langkah 3 Excel bersyarat).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-004')]
class TC_PBI04_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_004_kandidat_menghapus_pengalaman_kerja(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Ayu Wulandari', ['pengalaman' => [[self::BIDANG, self::DURASI, self::HASIL]]]);
            $this->assertCount(1, $this->pengalamanDb($kandidat['email']));

            // Langkah 1-3: buka bagian Pengalaman, klik aksi hapus.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#experience-list', self::BIDANG);
            $this->tandaiHalamanLama($browser);
            $this->klikTombolIkon($browser, '#experience-list button[aria-label="Hapus '.self::BIDANG.'"]');
            $this->tungguHalamanBaru($browser);

            // Langkah 4 / Expected: pengalaman terhapus dan tidak lagi ditampilkan.
            $browser->assertSee(self::MSG_EXP_DIHAPUS)
                ->assertSeeIn('#experience-list', self::MSG_EXP_KOSONG)
                ->assertDontSeeIn('#experience-list', self::BIDANG)
                ->assertSeeIn('#exp-total-duration', '0 pengalaman');
            $this->assertSame([], $this->pengalamanTampil($browser));
            $this->assertSame([], $this->pengalamanDb($kandidat['email']));
        });
    }
}
