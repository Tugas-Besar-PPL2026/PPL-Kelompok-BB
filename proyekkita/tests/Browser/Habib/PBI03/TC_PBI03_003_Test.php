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
 * TC-PBI03-003 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menghapus keterampilan dari profil.
 *
 * Penghapusan dilakukan dengan tombol x (aria-label "Hapus {nama}") pada tag keterampilan. Aplikasi tidak
 * menampilkan dialog konfirmasi (langkah 3 Excel bersyarat "jika terdapat konfirmasi").
 * Catatan Excel: penghapusan dari profil tidak berarti menghapus data master - ikut diverifikasi.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-003')]
class TC_PBI03_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_003_kandidat_menghapus_keterampilan_dari_profil(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Dimas Pratama', ['keterampilan' => [self::SKILL_FIGMA]]);
            $skillMaster = $this->skillDb(self::SKILL_FIGMA);

            // Langkah 1: login dan buka bagian Keterampilan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser)->assertSeeIn('#selected-skills', self::SKILL_FIGMA);

            // Langkah 2-3: klik aksi hapus pada keterampilan "Figma" (tanpa dialog konfirmasi).
            $this->hapusKeterampilanUi($browser, self::SKILL_FIGMA);

            // Langkah 4 / Expected: keterampilan tidak lagi tampil pada daftar keterampilan kandidat.
            $browser->assertDontSeeIn('#selected-skills', self::SKILL_FIGMA)
                ->assertSeeIn('#empty-selected-skills', self::MSG_SKILL_KOSONG)
                ->assertSeeIn('#skill-count', '0 dipilih');
            $this->assertSame([], $this->keterampilanTampil($browser));

            // Data master tetap ada dan aktif: kembali tersedia untuk dipilih ("+ tambah").
            $browser->type('#cari-keterampilan', self::SKILL_FIGMA)
                ->waitFor($this->barisSkill(self::SKILL_FIGMA).' .skill-add')
                ->assertSeeIn($this->barisSkill(self::SKILL_FIGMA), '+ tambah');

            $this->assertSame([], $this->keterampilanDb($kandidat['email']));
            $masterSesudah = $this->skillDb(self::SKILL_FIGMA);
            $this->assertNotNull($masterSesudah, 'Data master keterampilan tidak boleh ikut terhapus.');
            $this->assertSame((int) $skillMaster->id, (int) $masterSesudah->id);
            $this->assertTrue((bool) $masterSesudah->is_active);
        });
    }
}
