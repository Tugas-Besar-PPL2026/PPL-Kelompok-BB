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
 * TC-PBI03-002 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Keterampilan yang ditambahkan tetap tampil setelah halaman dimuat ulang.
 *
 * Persiapan lewat UI: kandidat baru mengisi Data Diri lalu menambahkan "Dusk Figma" (sebelum test dimulai).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-002')]
class TC_PBI03_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_002_keterampilan_tetap_tampil_setelah_muat_ulang(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: kandidat sudah menambahkan minimal satu keterampilan.
            $kandidat = $this->siapkanKandidatProfil($browser, 'Rina Wijaya', ['keterampilan' => [self::SKILL_FIGMA]]);

            // Langkah 1-2: login, buka halaman profil, keterampilan yang ditambahkan tampil.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#selected-skills', self::SKILL_FIGMA);
            $sebelum = $this->keterampilanTampil($browser);
            $this->assertSame([self::SKILL_FIGMA], $sebelum);

            // Langkah 3: muat ulang halaman (tandai halaman lama agar benar-benar menunggu halaman baru).
            $this->tandaiHalamanLama($browser);
            $browser->refresh();
            $this->tungguHalamanBaru($browser)->waitFor('#bagian-keterampilan');

            // Langkah 4 / Expected: keterampilan tetap tampil, data tidak hilang.
            $browser->assertSeeIn('#selected-skills', self::SKILL_FIGMA)
                ->assertSeeIn('#skill-count', '1 dipilih')
                ->assertMissing('#empty-selected-skills');
            $this->assertSame($sebelum, $this->keterampilanTampil($browser));

            // Data tetap ada setelah sesi berakhir: keluar lalu login kembali.
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->assertSame($sebelum, $this->keterampilanTampil($browser));

            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));
        });
    }
}
