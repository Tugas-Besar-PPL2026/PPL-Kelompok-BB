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
 * TC-PBI05-004 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil menghapus portofolio dari profil.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Ikon tempat sampah (aria-label "Hapus {judul}"). Aplikasi tidak menampilkan dialog konfirmasi (langkah 3 Excel bersyarat).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-004')]
class TC_PBI05_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_004_kandidat_menghapus_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Rizky Aditya', ['portofolio' => [[self::JUDUL, self::DESKRIPSI, self::TAUTAN]]]);

            // Langkah 1-3: buka Portofolio, klik aksi hapus.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser)->assertSeeIn('#portfolio-list', self::JUDUL);
            $this->tandaiHalamanLama($browser);
            $this->klikTombolIkon($browser, '#portfolio-list button[aria-label="Hapus '.self::JUDUL.'"]');
            $this->tungguHalamanBaru($browser);

            // Langkah 4 / Expected: portofolio terhapus dan tidak lagi ditampilkan.
            $browser->assertSee(self::MSG_PF_DIHAPUS)
                ->assertMissing('#portfolio-list')
                ->assertDontSee(self::DESKRIPSI);
            $this->assertSame([], $this->portofolioTampil($browser));
            $this->assertSame([], $this->portofolioDb($kandidat['email']));
        });
    }
}
