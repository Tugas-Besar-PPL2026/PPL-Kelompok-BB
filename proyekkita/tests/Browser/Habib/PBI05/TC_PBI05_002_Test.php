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
 * TC-PBI05-002 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Portofolio tetap tampil setelah halaman dimuat ulang.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Prasyarat dibuat lewat UI: kandidat baru menambahkan satu portofolio sebelum test dimulai.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-002')]
class TC_PBI05_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_002_portofolio_tetap_tampil_setelah_muat_ulang(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Farhan Akbar', ['portofolio' => [[self::JUDUL, self::DESKRIPSI, self::TAUTAN]]]);

            // Langkah 1-2: buka profil, portofolio tampil.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);
            $sebelum = $this->portofolioTampil($browser);
            $this->assertSame([['judul' => self::JUDUL, 'deskripsi' => self::DESKRIPSI, 'tautan' => 'example.com/portfolio', 'href' => self::TAUTAN, 'berkas' => null, 'href_berkas' => null]], $sebelum);

            // Langkah 3: muat ulang halaman.
            $this->tandaiHalamanLama($browser);
            $browser->refresh();
            $this->tungguHalamanBaru($browser)->waitFor('#portfolio-list');

            // Langkah 4 / Expected: portofolio tetap tampil dan informasi tidak berubah.
            $this->assertSame($sebelum, $this->portofolioTampil($browser));

            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);
            $this->assertSame($sebelum, $this->portofolioTampil($browser));
            $this->assertCount(1, $this->portofolioDb($kandidat['email']));
        });
    }
}
