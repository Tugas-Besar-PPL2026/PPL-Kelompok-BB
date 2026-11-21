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
 * TC-PBI05-007 (sheet PBI-05, Test_Case_ProyekKita_PBI03-05.xlsx).
 * Kandidat berhasil mengunggah dokumen portofolio (fitur lampiran tersedia).
 *
 * Form ada di tab "Portofolio" (/kandidat/profil/portofolio): #pf_title (Judul portofolio, wajib), #pf_link (Tautan,
 * opsional), #pf_description (Deskripsi singkat, opsional), #pf_file (Dokumen PDF/JPG/PNG maks. 10 MB, opsional).
 * Aturan tambahan aplikasi: minimal salah satu tautan atau dokumen wajib diisi.
 * Berkas uji: PDF valid 1 halaman yang dibuat saat test berjalan (storage/framework/testing/dusk-habib, dihapus di
 * tearDown). Setelah verifikasi, portofolio dihapus lewat UI agar berkas di storage/app/public ikut dibersihkan.
 */
#[Group('e2e-profil-kandidat')]
#[Group('pbi05')]
#[Group('TC-PBI05-007')]
class TC_PBI05_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use ProfilKandidatE2E;
    use UsesDuskTestDatabase;

    private const JUDUL = 'Desain Konten Media Sosial';

    private const DESKRIPSI = 'Kumpulan desain promosi produk.';

    private const TAUTAN = 'https://example.com/portfolio';

    public function test_TC_PBI05_007_kandidat_mengunggah_dokumen_portofolio(): void
    {
        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatProfil($browser, 'Nabila Azzahra');
            $pdf = $this->buatPdfUji();

            // Langkah 1-2: isi informasi portofolio, pilih dokumen yang didukung (tanpa tautan).
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaTabPortofolio($browser)->assertSeeIn('#pf-file-sub', 'PDF, JPG, atau PNG');
            $this->isiFormPortofolio($browser, self::JUDUL, self::DESKRIPSI, '');
            $this->pilihBerkasPortofolio($browser, $pdf);

            // Langkah 3: simpan.
            $this->simpanPortofolio($browser)->assertSee(self::MSG_PF_DITAMBAH);

            // Langkah 4 / Expected: portofolio tersimpan dan lampiran tampil serta dapat diakses.
            $kartu = $this->portofolioTampil($browser);
            $this->assertCount(1, $kartu);
            $this->assertSame(self::JUDUL, $kartu[0]['judul']);
            $this->assertNull($kartu[0]['tautan']);
            $this->assertStringContainsString(basename($pdf), (string) $kartu[0]['berkas']);
            $db = $this->portofolioDb($kandidat['email']);
            $this->assertCount(1, $db);
            $this->assertNotNull($db[0]->file_path);
            $this->assertStringEndsWith('/kandidat/profil/portofolio/'.$db[0]->id.'/berkas', (string) $kartu[0]['href_berkas']);

            // Lampiran dapat diakses oleh pemilik: diminta dari halaman yang sama (cookie sesi browser).
            $respons = $browser->driver->executeAsyncScript(
                'const done = arguments[arguments.length - 1];'
                .'fetch(arguments[0], {credentials: "same-origin"}).then(async r => done({status: r.status,'
                .' type: r.headers.get("content-type"), awal: (await r.text()).slice(0, 5)})).catch(e => done({status: 0, type: String(e)}));',
                [$kartu[0]['href_berkas']]
            );
            $this->assertSame(200, $respons['status']);
            $this->assertStringContainsString('application/pdf', (string) $respons['type']);
            $this->assertSame('%PDF-', $respons['awal']);

            // Pembersihan lewat UI (berkas di storage ikut dihapus oleh aplikasi).
            $this->tandaiHalamanLama($browser);
            $this->klikTombolIkon($browser, '#portfolio-list button[aria-label="Hapus '.self::JUDUL.'"]');
            $this->tungguHalamanBaru($browser)->assertSee(self::MSG_PF_DIHAPUS);
            $this->assertSame([], $this->portofolioDb($kandidat['email']));
        });
    }
}
