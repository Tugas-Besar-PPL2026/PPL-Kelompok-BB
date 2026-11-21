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
 * TC-PBI04-010 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Pengalaman kerja tidak tersimpan ketika proses penyimpanan gagal.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Simulasi kegagalan tanpa mengubah aplikasi: token CSRF form diganti nilai tidak valid di Chrome (setara sesi
 * kedaluwarsa / request ditolak server) sebelum kandidat menekan "Simpan Pengalaman" -> HTTP 419 "Page Expired".
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-010')]
class TC_PBI04_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_010_pengalaman_tidak_tersimpan_saat_penyimpanan_gagal(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Yusuf Maulana');

            // Langkah 1-2: buka form tambah pengalaman, isi data valid.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->isiFormPengalaman($browser, self::BIDANG, self::DURASI, self::HASIL);

            // Langkah 3: simulasikan kegagalan proses simpan.
            $this->rusakTokenCsrf($browser);
            $this->simpanPengalaman($browser);

            // Langkah 4 / Expected: informasi kegagalan tampil; setelah dimuat ulang data tidak tersimpan.
            $this->assertHalamanGalat($browser, 419, 'Page Expired');
            $browser->assertDontSee(self::MSG_EXP_DITAMBAH);

            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#experience-list', self::MSG_EXP_KOSONG)
                ->assertDontSeeIn('#experience-list', self::BIDANG);
            $this->assertSame([], $this->pengalamanDb($kandidat['email']));
        });
    }
}
