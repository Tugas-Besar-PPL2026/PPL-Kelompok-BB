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
 * TC-PBI03-006 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat dapat memperbarui daftar keterampilan pada profil.
 *
 * Hapus keterampilan A ("Dusk Figma"), tambahkan keterampilan B ("Dusk Copywriting").
 * Catatan implementasi: tidak ada tombol "Simpan perubahan" terpisah; setiap aksi hapus / "+ tambah" langsung
 * disimpan (form POST/DELETE per keterampilan). Langkah 4 Excel terpenuhi oleh penyimpanan per aksi tersebut.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-006')]
class TC_PBI03_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_006_kandidat_memperbarui_daftar_keterampilan(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Nadia Putri', ['keterampilan' => [self::SKILL_FIGMA]]);
            $this->assertSame([self::SKILL_FIGMA], $this->keterampilanDb($kandidat['email']));

            // Langkah 1: buka bagian pengelolaan keterampilan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#selected-skills', self::SKILL_FIGMA);

            // Langkah 2: hapus keterampilan A.
            $this->hapusKeterampilanUi($browser, self::SKILL_FIGMA);

            // Langkah 3-4: tambahkan keterampilan B yang aktif dan belum dimiliki (tersimpan langsung).
            $this->tambahKeterampilanUi($browser, self::SKILL_COPYWRITING);

            // Langkah 5 / Expected: A tidak tampil, B tampil.
            $browser->assertSeeIn('#selected-skills', self::SKILL_COPYWRITING)
                ->assertDontSeeIn('#selected-skills', self::SKILL_FIGMA)
                ->assertSeeIn('#skill-count', '1 dipilih');
            $this->assertSame([self::SKILL_COPYWRITING], $this->keterampilanTampil($browser));

            // Tetap sesuai setelah dimuat ulang.
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->assertSame([self::SKILL_COPYWRITING], $this->keterampilanTampil($browser));
            $this->assertSame([self::SKILL_COPYWRITING], $this->keterampilanDb($kandidat['email']));
        });
    }
}
