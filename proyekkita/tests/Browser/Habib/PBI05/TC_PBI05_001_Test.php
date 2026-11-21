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
 * TC-PBI05-001 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menambahkan portofolio dengan informasi karya valid.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.

 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-001')]
class TC_PBI05_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_001_kandidat_menambahkan_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Dian Purnama');

            // Langkah 1-2: buka bagian Portofolio, form "Tambah Portofolio".
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser)->assertSeeIn('#pf-form-title', 'Tambah Portofolio');

            // Langkah 3-5: isi judul, deskripsi, tautan lalu simpan.
            $this->isiFormPortofolio($browser, self::JUDUL, self::DESKRIPSI, self::TAUTAN);
            $this->simpanPortofolio($browser);

            // Langkah 6 / Expected: tersimpan dan tampil dengan informasi yang sesuai.
            $browser->assertPathIs('/kandidat/profil/portofolio')
                ->assertSee(self::MSG_PF_DITAMBAH)
                ->assertMissing('#form-portofolio .hint.err');
            $this->assertSame([['judul' => self::JUDUL, 'deskripsi' => self::DESKRIPSI, 'tautan' => 'example.com/portfolio', 'href' => self::TAUTAN, 'berkas' => null, 'href_berkas' => null]], $this->portofolioTampil($browser));
            $this->assertSame('_blank', $browser->attribute('#portfolio-list a.pf-link', 'target'));

            $db = $this->portofolioDb($kandidat['email']);
            $this->assertCount(1, $db);
            $this->assertSame([self::JUDUL, self::DESKRIPSI, self::TAUTAN], [$db[0]->title, $db[0]->description, $db[0]->link]);
            $this->assertNull($db[0]->file_path);
        });
    }
}
