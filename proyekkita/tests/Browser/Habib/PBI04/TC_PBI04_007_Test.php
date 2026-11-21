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
 * TC-PBI04-007 (sheet PBI-04, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat dapat memiliki lebih dari satu pengalaman kerja.
 *
 * Form pengalaman ada di tab "Keterampilan & Pengalaman" (/kandidat/profil): field #work_type
 * (Bidang / jenis pekerjaan, wajib), #duration (Durasi, wajib, teks bebas maks. 50), #exp_description
 * (Deskripsi pekerjaan = "hasil pekerjaan" pada Excel, opsional).

 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi04')]
#[Group('TC-PBI04-007')]
class TC_PBI04_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const BIDANG = 'Desain Grafis';

    private const DURASI = '6 bulan';

    private const HASIL = 'Membuat materi promosi digital untuk usaha kecil.';

    public function test_TC_PBI04_007_kandidat_memiliki_lebih_dari_satu_pengalaman(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Galih Prasetyo', ['pengalaman' => [[self::BIDANG, self::DURASI, self::HASIL]]]);
            $pertama = $this->pengalamanDb($kandidat['email'])[0];
            $kedua = ['Penulisan Konten Media Sosial', '3 bulan', 'Menulis 30 caption promosi untuk toko daring.'];

            // Langkah 1-2: tambahkan pengalaman kedua dengan data berbeda lalu simpan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->tambahPengalamanUi($browser, ...$kedua);

            // Langkah 3 / Expected: kedua pengalaman tampil terpisah dan data pertama tidak tertimpa.
            $this->assertSame([[self::BIDANG, self::DURASI, self::HASIL], $kedua], $this->pengalamanTampil($browser));
            $browser->assertSeeIn('#exp-total-duration', 'Total 9 bln');

            $db = $this->pengalamanDb($kandidat['email']);
            $this->assertCount(2, $db);
            $this->assertSame((int) $pertama->id, (int) $db[0]->id);
            $this->assertSame([self::BIDANG, self::DURASI, self::HASIL], [$db[0]->work_type, $db[0]->duration, $db[0]->description]);
            $this->assertSame($kedua, [$db[1]->work_type, $db[1]->duration, $db[1]->description]);
        });
    }
}
