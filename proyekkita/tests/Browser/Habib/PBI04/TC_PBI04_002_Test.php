<?php

namespace Tests\Browser\Habib\PBI04;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI04-002 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Data pengalaman kerja tetap tersimpan setelah halaman dimuat ulang.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).
 * Prasyarat dibuat lewat UI: kandidat baru menambahkan satu pengalaman sebelum test dimulai.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-002')]
class TC_PBI04_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_002_pengalaman_tetap_tampil_setelah_muat_ulang(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Putri Handayani', ['pengalaman' => [[self::BIDANG, self::DURASI, self::HASIL]]]);

            // Langkah 1-2: buka profil, pengalaman yang dibuat tampil.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $sebelum = $this->pengalamanTampil($browser);
            $this->assertSame([[self::BIDANG, self::DURASI, self::HASIL]], $sebelum);

            // Langkah 3: muat ulang halaman.
            $this->tandaiHalamanLama($browser);
            $browser->refresh();
            $this->tungguHalamanBaru($browser)->waitFor('#experience-list');

            // Langkah 4 / Expected: data tetap tampil dan setiap nilai tidak berubah.
            $this->assertSame($sebelum, $this->pengalamanTampil($browser));
            $browser->assertSeeIn('#exp-total-duration', 'Total 6 bln');

            // Juga setelah keluar dan login kembali.
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabKeterampilanPengalaman($browser);
            $this->assertSame($sebelum, $this->pengalamanTampil($browser));
            $this->assertCount(1, $this->pengalamanDb($kandidat['email']));
        });
    }
}
