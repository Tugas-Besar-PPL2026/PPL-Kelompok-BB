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
 * TC-PBI03-005 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat hanya dapat memilih keterampilan yang tersedia (aktif) pada data master.
 *
 * Persiapan lewat UI: Admin memastikan "Dusk Figma" Aktif dan "Dusk Keterampilan Nonaktif" Nonaktif
 * di halaman Data Master. Selain pencarian di UI, pengiriman ID keterampilan nonaktif dari browser juga
 * diverifikasi ditolak server (validasi exists + is_active pada CandidateSkillRequest).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-005')]
class TC_PBI03_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_005_hanya_keterampilan_aktif_dari_data_master_yang_dapat_dipilih(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Andi Saputra');
            $nonaktif = $this->skillDb(self::SKILL_NONAKTIF);
            $this->assertNotNull($nonaktif);
            $this->assertFalse((bool) $nonaktif->is_active, 'Prasyarat: keterampilan nonaktif pada data master.');
            $this->assertTrue((bool) $this->skillDb(self::SKILL_FIGMA)->is_active, 'Prasyarat: keterampilan aktif pada data master.');

            // Langkah 1-2: buka pengelolaan keterampilan dan periksa pilihan yang tersedia.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)
                ->assertSee('Keterampilan nonaktif tidak ditampilkan.');

            // Pilihan yang tersedia sama persis dengan keterampilan AKTIF pada data master (urut id).
            $pilihan = $browser->driver->executeScript(
                "return [...document.querySelectorAll('#skill-list [data-skill-row] > span:first-child')].map(s => s.textContent.trim());"
            );
            $aktifDb = \Illuminate\Support\Facades\DB::table('skills')->where('is_active', true)->orderBy('id')->pluck('name')->all();
            $this->assertSame($aktifDb, $pilihan);

            // Langkah 3: cari keterampilan aktif -> tersedia untuk dipilih.
            $browser->type('#cari-keterampilan', self::SKILL_FIGMA)
                ->waitFor($this->barisSkill(self::SKILL_FIGMA).' .skill-add')
                ->assertVisible($this->barisSkill(self::SKILL_FIGMA).' .skill-add');

            // Langkah 4: cari keterampilan nonaktif -> tidak tersedia.
            $browser->clear('#cari-keterampilan')->type('#cari-keterampilan', self::SKILL_NONAKTIF)
                ->assertMissing($this->barisSkill(self::SKILL_NONAKTIF))
                ->assertVisible('#skill-empty-search')
                ->assertSeeIn('#skill-empty-search', 'Keterampilan tidak ditemukan.');

            // Penambahan keterampilan nonaktif yang dipaksa dari browser ditolak.
            $this->kirimFormManipulasi($browser, 'POST', '/kandidat/profil/keterampilan', [['skill_id', (string) $nonaktif->id]]);
            $browser->assertPathIs('/kandidat/profil')
                ->assertSeeIn('#bagian-keterampilan .hint.err', self::MSG_SKILL_TIDAK_TERSEDIA)
                ->assertDontSeeIn('#selected-skills', self::SKILL_NONAKTIF);

            // Keterampilan aktif tetap dapat ditambahkan.
            $this->tambahKeterampilanUi($browser, self::SKILL_FIGMA);
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));
        });
    }
}
