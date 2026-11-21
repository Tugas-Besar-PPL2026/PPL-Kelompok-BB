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
 * TC-PBI05-010 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Profil kandidat dapat ditampilkan ketika belum memiliki portofolio.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Implementasi tidak memiliki teks empty state khusus pada tab Portofolio: daftar kartu (#portfolio-list) tidak dirender,
 * form "Tambah Portofolio" langsung tampil, dan panel Kelengkapan Profil menandai "Minimal 1 portofolio" belum terpenuhi.
 * Kondisi tersebut yang diverifikasi sebagai "informasi yang sesuai" (lihat README).
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-010')]
class TC_PBI05_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_010_profil_tanpa_portofolio_tetap_dapat_diakses(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Aldi Nugraha');

            // Langkah 1-2: buka profil, amati bagian Portofolio.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser)
                ->assertMissing('#portfolio-list')
                ->assertSeeIn('[data-cek="portofolio"] + span', 'Minimal 1 portofolio');
            $this->assertStringNotContainsString(' on ', ' '.$browser->attribute('[data-cek="portofolio"]', 'class').' ');

            // Langkah 3: form tambah portofolio tersedia.
            $browser->assertSeeIn('#pf-form-title', 'Tambah Portofolio')
                ->assertVisible('#pf_title')->assertVisible('#pf_link')->assertVisible('#pf_description')
                ->assertSeeIn('#btn-submit-pf', 'Simpan Portofolio');

            // Expected: portofolio dapat ditambahkan.
            $this->isiFormPortofolio($browser, self::JUDUL, self::DESKRIPSI, self::TAUTAN);
            $this->simpanPortofolio($browser)->assertSee(self::MSG_PF_DITAMBAH);
            $browser->assertPresent('#portfolio-list');
            $this->assertStringContainsString(' on ', ' '.$browser->attribute('[data-cek="portofolio"]', 'class').' ');
            $this->assertCount(1, $this->portofolioDb($kandidat['email']));
        });
    }
}
