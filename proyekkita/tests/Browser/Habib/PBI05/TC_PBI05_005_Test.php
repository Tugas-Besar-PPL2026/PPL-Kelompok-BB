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
 * TC-PBI05-005 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Portofolio tidak dapat disimpan ketika informasi wajib kosong.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Field wajib sesuai CandidatePortfolioRequest: title (required) dan minimal salah satu tautan/dokumen.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-005')]
class TC_PBI05_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_005_validasi_field_wajib_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Melati Kusuma');

            // Langkah 1: buka form tambah portofolio.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);

            // Langkah 2-4 (a): kosongkan judul dan tautan/dokumen, simpan, amati validasi.
            $this->isiFormPortofolio($browser, '', self::DESKRIPSI, '');
            $this->simpanPortofolio($browser)->assertDontSee(self::MSG_PF_DITAMBAH);
            $this->assertSame('Judul portofolio wajib diisi.', $this->galatField($browser, 'pf_title'));
            $this->assertSame(self::MSG_PF_TAUTAN_ATAU_DOKUMEN, $this->galatField($browser, 'pf_link'));
            $browser->assertInputValue('#pf_description', self::DESKRIPSI);

            // (b): hanya judul yang kosong.
            $this->isiFormPortofolio($browser, '', self::DESKRIPSI, self::TAUTAN);
            $this->simpanPortofolio($browser)->assertDontSee(self::MSG_PF_DITAMBAH);
            $this->assertSame('Judul portofolio wajib diisi.', $this->galatField($browser, 'pf_title'));
            $this->assertNull($this->galatField($browser, 'pf_link'));

            // Expected: tidak ada portofolio baru yang tersimpan.
            $browser->assertMissing('#portfolio-list');
            $this->assertSame([], $this->portofolioDb($kandidat['email']));
        });
    }
}
