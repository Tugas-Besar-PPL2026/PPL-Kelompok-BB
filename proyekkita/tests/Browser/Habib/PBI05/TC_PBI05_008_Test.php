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
 * TC-PBI05-008 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * File portofolio dengan format yang tidak didukung ditolak.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Format yang diizinkan aplikasi: PDF, JPG, JPEG, PNG (mimes). Berkas uji ".exe" berisi teks tiruan (bukan program),
 * dibuat saat test berjalan dan dihapus di tearDown. Atribut accept pada input hanya membatasi dialog pilih file;
 * berkas tetap dapat dikirim, sehingga validasi server yang diuji.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-008')]
class TC_PBI05_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_008_dokumen_format_tidak_didukung_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Bayu Pamungkas');
            $exe = $this->buatExeUji();

            // Langkah 1-2: isi informasi portofolio, pilih file .exe.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);
            $this->isiFormPortofolio($browser, self::JUDUL, self::DESKRIPSI, '');
            $this->pilihBerkasPortofolio($browser, $exe);

            // Langkah 3-4: simpan, amati pesan validasi.
            $this->simpanPortofolio($browser)->assertDontSee(self::MSG_PF_DITAMBAH);
            $this->assertSame(self::MSG_PF_FORMAT_BERKAS, $this->galatField($browser, 'pf_file'));
            $browser->assertPresent('#pf-dropzone.err');

            // Expected: lampiran (dan portofolio) tidak tersimpan.
            $browser->assertMissing('#portfolio-list');
            $this->assertSame([], $this->portofolioDb($kandidat['email']));
        });
    }
}
