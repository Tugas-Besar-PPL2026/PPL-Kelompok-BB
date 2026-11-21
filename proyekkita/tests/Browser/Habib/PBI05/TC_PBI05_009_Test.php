<?php

namespace Tests\Browser\Habib\PBI05;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Habib\Concerns\ProfilKandidatE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI05-009 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat dapat menambahkan lebih dari satu portofolio.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.

 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-009')]
class TC_PBI05_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_009_kandidat_menambahkan_lebih_dari_satu_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Kartika Sari', ['portofolio' => [[self::JUDUL, self::DESKRIPSI, self::TAUTAN]]]);
            $pertama = $this->portofolioDb($kandidat['email'])[0];
            $kedua = ['Video Promosi Produk Kopi', 'Video pendek 30 detik untuk media sosial.', 'https://example.com/video-kopi'];

            // Langkah 1-2: tambahkan portofolio kedua dengan informasi berbeda lalu simpan.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);
            $this->isiFormPortofolio($browser, ...$kedua);
            $this->simpanPortofolio($browser)->assertSee(self::MSG_PF_DITAMBAH);

            // Langkah 3 / Expected: kedua portofolio tampil terpisah; data pertama tidak tertimpa.
            $kartu = $this->portofolioTampil($browser);
            $this->assertCount(2, $kartu);
            $this->assertSame(['judul' => self::JUDUL, 'deskripsi' => self::DESKRIPSI, 'tautan' => 'example.com/portfolio', 'href' => self::TAUTAN, 'berkas' => null, 'href_berkas' => null], $kartu[0]);
            $this->assertSame([$kedua[0], $kedua[1], $kedua[2]], [$kartu[1]['judul'], $kartu[1]['deskripsi'], $kartu[1]['href']]);

            $db = $this->portofolioDb($kandidat['email']);
            $this->assertCount(2, $db);
            $this->assertSame((int) $pertama->id, (int) $db[0]->id);
            $this->assertSame([self::JUDUL, self::DESKRIPSI, self::TAUTAN], [$db[0]->title, $db[0]->description, $db[0]->link]);
            $this->assertSame($kedua, [$db[1]->title, $db[1]->description, $db[1]->link]);
        });
    }
}
