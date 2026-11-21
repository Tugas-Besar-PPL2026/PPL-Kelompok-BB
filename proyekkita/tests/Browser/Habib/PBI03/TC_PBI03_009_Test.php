<?php

namespace Tests\Browser\Habib\PBI03;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI03-009 (sheet PBI-03, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Keterampilan kandidat yang dipilih dapat ditampilkan pada profil kandidat.
 *
 * Prasyarat "keterampilan yang juga digunakan pada kebutuhan proyek" dibuat lewat UI: UMKM bersama (terverifikasi)
 * menyimpan draf proyek yang membutuhkan keterampilan "Dusk Desain Grafis". Kandidat lalu memilih keterampilan
 * tersebut + "Dusk Figma". Nama yang tampil dibandingkan dengan data master dan relasi profil (read-only).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi03')]
#[Group('TC-PBI03-009')]
class TC_PBI03_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    public function test_TC_PBI03_009_keterampilan_ditampilkan_sesuai_data_master_dan_relasi_profil(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat proyek: draf proyek UMKM yang membutuhkan KETERAMPILAN_E2E.
            $this->pastikanUmkmTerverifikasi($browser);
            $this->masukSebagaiUmkm($browser);
            $proyekId = $this->buatProyek($browser, "Desain Kemasan Produk {$this->kodeUnik()}", 'draf');
            $this->keluar($browser);
            $skillProyek = $this->skillDb(self::KETERAMPILAN_E2E);
            $this->assertTrue(
                DB::table('project_skill')->where('project_id', $proyekId)->where('skill_id', $skillProyek->id)->exists(),
                'Prasyarat: keterampilan digunakan pada kebutuhan proyek.'
            );

            $kandidat = $this->siapkanKandidatProfil($browser, 'Arif Hidayat', [
                'keterampilan' => [self::KETERAMPILAN_E2E, self::SKILL_FIGMA],
            ]);

            // Langkah 1-2: buka profil kandidat dan periksa daftar keterampilan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $tampil = $this->keterampilanTampil($browser);

            // Langkah 3 / Expected: nama sesuai data master dan relasi profil kandidat.
            $namaMaster = DB::table('skills')
                ->whereIn('id', [$skillProyek->id, $this->skillDb(self::SKILL_FIGMA)->id])
                ->orderBy('id')->pluck('name')->all();
            $this->assertSame($namaMaster, $tampil);
            $this->assertSame($this->keterampilanDb($kandidat['email']), $tampil);
            $browser->assertSeeIn('#skill-count', count($tampil).' dipilih');
            foreach ($tampil as $nama) {
                $browser->assertSeeIn($this->barisSkill($nama), 'sudah dipilih');
            }
        });
    }
}
