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
 * TC-PBI03-008 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Pengguna selain pemilik profil tidak dapat mengubah keterampilan kandidat.
 *
 * Mekanisme route aktual: semua route keterampilan (/kandidat/profil/keterampilan[/{skill}]) TIDAK menerima ID
 * profil; controller selalu memakai profil milik pengguna yang login (+ Gate CandidateProfilePolicy::update).
 * Maka langkah "akses URL/aksi edit keterampilan milik kandidat A" diuji dengan:
 *  (1) Kandidat B mengirim aksi hapus untuk keterampilan yang dimiliki A dan aksi perbarui daftar (PUT kosong)
 *      dari browser -> hanya memengaruhi profil B sendiri; profil A tidak tersentuh dan tidak pernah tampil.
 *  (2) Pengguna non-kandidat (UMKM) membuka halaman edit keterampilan -> 403.
 * Expected Excel: sistem menolak akses / mengarahkan ke halaman yang sesuai dan data A tidak berubah.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-008')]
class TC_PBI03_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_008_pengguna_lain_tidak_dapat_mengubah_keterampilan_kandidat(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidatA = $this->siapkanKandidatProfil($browser, 'Raka Permana', ['keterampilan' => [self::SKILL_FIGMA, self::SKILL_COPYWRITING]]);
            $kandidatB = $this->siapkanKandidatProfil($browser, 'Dewi Lestari');
            $sebelumA = $this->keterampilanDb($kandidatA['email']);
            $this->assertSame([self::SKILL_FIGMA, self::SKILL_COPYWRITING], $sebelumA);
            $idFigma = (int) $this->skillDb(self::SKILL_FIGMA)->id;

            // Langkah 1: sebagai kandidat B, coba aksi hapus dan perbarui keterampilan.
            $this->masukSebagaiKandidat($browser, $kandidatB);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->kirimFormManipulasi($browser, 'DELETE', "/kandidat/profil/keterampilan/{$idFigma}");

            // Langkah 2: respons sistem - diarahkan ke profil B sendiri; data A tidak ditampilkan.
            $browser->assertPathIs('/kandidat/profil')
                ->assertSeeIn('#empty-selected-skills', self::MSG_SKILL_KOSONG)
                ->assertDontSeeIn('#selected-skills', self::SKILL_FIGMA)
                ->assertDontSee($kandidatA['nama']);

            $this->kirimFormManipulasi($browser, 'PUT', '/kandidat/profil/keterampilan', [['skills', '']]);
            $browser->assertDontSee($kandidatA['nama']);
            $this->assertSame([], $this->keterampilanDb($kandidatB['email']));
            $this->keluar($browser);

            // Pengguna dengan peran lain (UMKM) membuka halaman edit keterampilan kandidat -> ditolak (403).
            $kode = $this->kodeUnik();
            $this->daftar($browser, 'umkm', 'Hendro Susanto', "hendro.susanto.{$kode}@proyekkita.test", self::SANDI_E2E);
            $browser->visit('/kandidat/profil/keterampilan');
            $this->assertHalamanGalat($browser, 403, self::MSG_TANPA_AKSES);
            $browser->assertDontSee(self::SKILL_FIGMA);
            $browser->visit('/umkm/profil');
            $this->keluar($browser);

            // Langkah 3 / Expected: data keterampilan kandidat A tidak berubah.
            $this->assertSame($sebelumA, $this->keterampilanDb($kandidatA['email']));
            $this->masukSebagaiKandidat($browser, $kandidatA);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->assertSame([self::SKILL_FIGMA, self::SKILL_COPYWRITING], $this->keterampilanTampil($browser));
        });
    }
}
