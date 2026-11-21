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
 * TC-PBI05-006 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Tautan portofolio dengan format tidak valid ditolak.
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 *
 * TEMUAN: validasi tautan (SelectionPresenter::safeUrl) menambahkan "https://" otomatis pada teks tanpa skema, sehingga
 * input "abc" DITERIMA dan disimpan sebagai "https://abc". Input "https://" (tidak lengkap) ditolak dengan benar.
 * Test tetap menguji Expected Result Excel untuk kedua input sehingga GAGAL pada "abc". Lihat README bagian Temuan.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-006')]
#[Group('temuan-aplikasi')]
class TC_PBI05_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_006_tautan_tidak_valid_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Wahyu Setiawan');
            $this->masukSebagaiKandidat($browser, $kandidat);

            $diterima = [];
            foreach (['https://', 'abc'] as $tautan) {
                // Langkah 1-3: informasi lain valid, tautan tidak valid, klik simpan.
                $this->bukaTabPortofolio($browser);
                $this->isiFormPortofolio($browser, "Portofolio tautan {$tautan}", self::DESKRIPSI, $tautan);
                $this->simpanPortofolio($browser);

                // Langkah 4: amati pesan validasi pada field Tautan.
                $galat = $this->galatField($browser, 'pf_link');
                if ($galat === null) {
                    $diterima[] = $tautan;
                } else {
                    $this->assertSame(self::MSG_PF_URL_TIDAK_VALID, $galat);
                    $browser->assertDontSee(self::MSG_PF_DITAMBAH);
                }
            }

            $tersimpan = array_map(fn ($p) => $p->link, $this->portofolioDb($kandidat['email']));

            // Expected: setiap tautan tidak valid ditolak dan data tidak tersimpan.
            $this->assertSame([], $diterima,
                'Tautan tidak valid diterima aplikasi: '.json_encode($diterima).'. Tersimpan sebagai: '.json_encode($tersimpan).'.');
            $this->assertSame([], $tersimpan);
        });
    }
}
