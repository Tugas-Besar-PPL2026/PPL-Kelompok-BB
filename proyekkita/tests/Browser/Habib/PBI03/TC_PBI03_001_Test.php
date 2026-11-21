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
 * TC-PBI03-001 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menambahkan keterampilan yang tersedia pada data master ke profil.
 *
 * Persiapan lewat UI: Admin memastikan master "Dusk Figma" aktif; kandidat baru mendaftar dan mengisi Data Diri.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-001')]
class TC_PBI03_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_001_kandidat_menambahkan_keterampilan_dari_data_master(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: kandidat sudah punya profil dan belum memiliki keterampilan Figma.
            $kandidat = $this->siapkanKandidatProfil($browser, 'Budi Santoso');
            $this->assertSame([], $this->keterampilanDb($kandidat['email']));

            // Kandidat login, berada di halaman Profil & Portofolio.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah 1-2: buka bagian Keterampilan, gunakan kolom "Tambah keterampilan".
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSeeIn('#bagian-keterampilan h3', 'Keterampilan')
                ->assertSeeIn('#skill-count', '0 dipilih');

            // Langkah 3-4: pilih keterampilan aktif "Figma" lalu simpan (+ tambah).
            $this->tambahKeterampilanUi($browser, self::SKILL_FIGMA);

            // Langkah 5 / Expected: keterampilan muncul pada profil, tersimpan, tanpa pesan error.
            $browser->assertPathIs('/kandidat/profil')
                ->assertSeeIn('#selected-skills', self::SKILL_FIGMA)
                ->assertSeeIn('#skill-count', '1 dipilih')
                ->assertMissing('#empty-selected-skills')
                ->assertSeeIn($this->barisSkill(self::SKILL_FIGMA), 'sudah dipilih')
                ->assertMissing($this->barisSkill(self::SKILL_FIGMA).' .skill-add')
                ->assertMissing('#bagian-keterampilan .hint.err')
                ->assertMissing('.al.bad');
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanTampil($browser));

            // Verifikasi tambahan (read-only): relasi kandidat-keterampilan tersimpan.
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));
        });
    }
}
