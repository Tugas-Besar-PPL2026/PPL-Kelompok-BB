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
 * TC-PBI05-003 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil memperbarui informasi portofolio.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Ikon pensil (aria-label "Ubah {judul}") mengisi form dan mengubahnya menjadi mode "Ubah Portofolio".
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-003')]
class TC_PBI05_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_003_kandidat_memperbarui_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Intan Permatasari', ['portofolio' => [[self::JUDUL, self::DESKRIPSI, self::TAUTAN]]]);
            $idAwal = (int) $this->portofolioDb($kandidat['email'])[0]->id;
            $judulBaru = 'Desain Konten Media Sosial UMKM Kuliner';
            $deskripsiBaru = 'Kumpulan 12 desain promosi produk makanan.';

            // Langkah 1-2: buka bagian Portofolio, pilih portofolio yang akan diedit.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser);
            $this->klikTombolIkon($browser, '#portfolio-list a[aria-label="Ubah '.self::JUDUL.'"]');
            $browser->waitForTextIn('#pf-form-title', 'Ubah Portofolio')
                ->assertInputValue('#pf_title', self::JUDUL)
                ->assertInputValue('#pf_link', self::TAUTAN)
                ->assertInputValue('#pf_description', self::DESKRIPSI);

            // Langkah 3-4: ubah judul dan deskripsi lalu simpan.
            $this->isiFormPortofolio($browser, $judulBaru, $deskripsiBaru, self::TAUTAN);
            $this->simpanPortofolio($browser);

            // Langkah 5 / Expected: informasi diperbarui dan tersimpan.
            $browser->assertSee(self::MSG_PF_DIUBAH)->assertSeeIn('#pf-form-title', 'Tambah Portofolio');
            $kartu = $this->portofolioTampil($browser);
            $this->assertCount(1, $kartu);
            $this->assertSame([$judulBaru, $deskripsiBaru], [$kartu[0]['judul'], $kartu[0]['deskripsi']]);
            $browser->assertDontSeeIn('#portfolio-list', self::DESKRIPSI);

            $db = $this->portofolioDb($kandidat['email']);
            $this->assertCount(1, $db);
            $this->assertSame($idAwal, (int) $db[0]->id);
            $this->assertSame([$judulBaru, $deskripsiBaru, self::TAUTAN], [$db[0]->title, $db[0]->description, $db[0]->link]);
        });
    }
}
