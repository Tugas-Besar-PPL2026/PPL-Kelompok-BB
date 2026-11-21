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
 * TC-PBI04-001 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menambahkan pengalaman kerja dengan data valid.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).

 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-001')]
class TC_PBI04_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_001_kandidat_menambahkan_pengalaman_kerja(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Reza Firmansyah');

            // Langkah 1-2: login, buka bagian Pengalaman, form "Tambah pengalaman".
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#bagian-pengalaman h3', 'Pengalaman Kerja')
                ->assertSeeIn('#exp-form-title', 'Tambah pengalaman');

            // Langkah 3-4: isi informasi pengalaman kerja lalu simpan.
            $this->isiFormPengalaman($browser, self::BIDANG, self::DURASI, self::HASIL);
            $this->simpanPengalaman($browser);

            // Langkah 5 / Expected: tersimpan dan tampil dengan informasi yang sesuai.
            $browser->assertPathIs('/kandidat/profil')
                ->assertSee(self::MSG_EXP_DITAMBAH)
                ->assertMissing('#bagian-pengalaman .hint.err')
                ->assertSeeIn('#exp-total-duration', 'Total 6 bln');
            $this->assertSame([[self::BIDANG, self::DURASI, self::HASIL]], $this->pengalamanTampil($browser));

            $db = $this->pengalamanDb($kandidat['email']);
            $this->assertCount(1, $db);
            $this->assertSame([self::BIDANG, self::DURASI, self::HASIL], [$db[0]->work_type, $db[0]->duration, $db[0]->description]);
        });
    }
}
