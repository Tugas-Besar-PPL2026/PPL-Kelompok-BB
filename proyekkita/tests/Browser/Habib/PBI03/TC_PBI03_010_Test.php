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
 * TC-PBI03-010 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Perubahan keterampilan tidak tersimpan ketika proses penyimpanan gagal.
 *
 * Simulasi kegagalan tanpa mengubah aplikasi: token CSRF form diganti nilai tidak valid di Chrome (setara sesi
 * kedaluwarsa / request ditolak server), lalu kandidat menekan "+ tambah" seperti biasa. Server menolak request
 * (HTTP 419 "Page Expired") dan tidak ada perubahan yang tersimpan.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-010')]
class TC_PBI03_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_010_keterampilan_tidak_tersimpan_saat_penyimpanan_gagal(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Maya Sari');

            // Langkah 1-2: buka pengelolaan keterampilan, pilih keterampilan baru.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->type('#cari-keterampilan', self::SKILL_FIGMA)
                ->waitFor($this->barisSkill(self::SKILL_FIGMA).' .skill-add');

            // Langkah 3: simulasikan kegagalan proses simpan.
            $this->rusakTokenCsrf($browser);
            $this->tandaiHalamanLama($browser);
            $browser->click($this->barisSkill(self::SKILL_FIGMA).' .skill-add');
            $this->tungguHalamanBaru($browser);

            // Langkah 4 / Expected: informasi kegagalan tampil.
            $this->assertHalamanGalat($browser, 419, 'Page Expired');
            $browser->assertDontSee(self::MSG_SKILL_DITAMBAH);

            // Setelah halaman profil dimuat ulang, perubahan tidak tersimpan permanen.
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#empty-selected-skills', self::MSG_SKILL_KOSONG)
                ->assertSeeIn('#skill-count', '0 dipilih')
                ->assertDontSeeIn('#selected-skills', self::SKILL_FIGMA);
            $this->assertSame([], $this->keterampilanDb($kandidat['email']));
        });
    }
}
