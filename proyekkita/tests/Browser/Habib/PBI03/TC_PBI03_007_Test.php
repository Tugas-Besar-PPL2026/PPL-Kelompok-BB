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
 * TC-PBI03-007 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Profil kandidat dapat ditampilkan ketika belum memiliki keterampilan.
 *
 * "Halaman edit keterampilan" = route GET /kandidat/profil/keterampilan (kandidat.profil.keterampilan.edit),
 * yang menampilkan view yang sama dengan /kandidat/profil.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-007')]
class TC_PBI03_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_007_profil_tanpa_keterampilan_tetap_dapat_diakses(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Fajar Nugroho');
            $this->assertSame([], $this->keterampilanDb($kandidat['email']));

            // Langkah 1-2: buka halaman profil kandidat, amati bagian Keterampilan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#empty-selected-skills', self::MSG_SKILL_KOSONG)
                ->assertSeeIn('#skill-count', '0 dipilih')
                ->assertMissing('#selected-skills .tag.on');
            // Panel Kelengkapan Profil: "Minimal 1 keterampilan" belum tercentang.
            $this->assertStringNotContainsString(' on ', ' '.$browser->attribute('[data-cek="keterampilan"]', 'class').' ');

            // Langkah 3: buka halaman edit keterampilan.
            $browser->visit('/kandidat/profil/keterampilan')
                ->waitFor('#bagian-keterampilan')
                ->assertPathIs('/kandidat/profil/keterampilan')
                ->assertSeeIn('#empty-selected-skills', self::MSG_SKILL_KOSONG)
                ->assertVisible('#cari-keterampilan');

            // Expected: kandidat tetap dapat menambahkan keterampilan.
            $this->tambahKeterampilanUi($browser, self::SKILL_FIGMA);
            $browser->assertSeeIn('#selected-skills', self::SKILL_FIGMA)->assertMissing('#empty-selected-skills');
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));
        });
    }
}
