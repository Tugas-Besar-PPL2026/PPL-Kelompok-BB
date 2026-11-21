<?php

namespace Tests\Browser\Habib\PBI03;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI03-004 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat tidak dapat menambahkan keterampilan yang sama lebih dari satu kali.
 *
 * Kontrol UI: keterampilan yang sudah dimiliki ditandai "sudah dipilih" dan tidak memiliki tombol "+ tambah".
 * Langkah 3 Excel ("Simpan jika kontrol aplikasi memungkinkan") diuji dengan mengirim ulang form tambah
 * untuk keterampilan yang sama dari halaman di Chrome (memakai sesi dan token CSRF asli), seperti pengguna
 * yang memanipulasi form lewat DevTools / mengirim ulang form lama.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-004')]
class TC_PBI03_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_004_keterampilan_duplikat_dicegah(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Siti Rahmawati', ['keterampilan' => [self::SKILL_FIGMA]]);
            $idFigma = (int) $this->skillDb(self::SKILL_FIGMA)->id;

            // Langkah 1-2: buka pengelolaan keterampilan, coba pilih keterampilan yang sudah dimiliki.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->type('#cari-keterampilan', self::SKILL_FIGMA)
                ->waitFor($this->barisSkill(self::SKILL_FIGMA))
                ->assertSeeIn($this->barisSkill(self::SKILL_FIGMA), 'sudah dipilih')
                ->assertMissing($this->barisSkill(self::SKILL_FIGMA).' .skill-add');

            // Langkah 3: paksa simpan keterampilan yang sama (form tambah dikirim dari browser).
            $this->kirimFormManipulasi($browser, 'POST', '/kandidat/profil/keterampilan', [['skill_id', (string) $idFigma]]);

            // Langkah 4 / Expected: tidak ada entri kedua, keterampilan hanya tercatat satu kali.
            $browser->assertPathIs('/kandidat/profil')
                ->assertSee(self::MSG_SKILL_SUDAH_ADA)
                ->assertSeeIn('#skill-count', '1 dipilih');
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanTampil($browser));
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));
        });
    }
}
