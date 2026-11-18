<?php

namespace Tests\Browser\Aisyah\PBI14;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI14-002 (sheet PBI 14, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM "Warung Uji PBI14 [kode]" terverifikasi, proyek dibuat lewat form dan disetujui Admin;
 *              satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, Lihat Detail & Lamar, periksa halaman detail.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi14')]
#[Group('TC-PBI14-002')]
class TC_PBI14_002_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI14-002 - Tombol Lihat Detail & Lamar membuka halaman detail yang lengkap.
     */
    public function test_TC_PBI14_002_detail_proyek_lengkap(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: proyek Dipublikasikan milik UMKM terverifikasi, akun kandidat terdaftar.
            $umkm = $this->siapkanUmkmBaru($browser, 'PBI14', $this->profilWarungUji());
            $data = $this->dataProyekPbi14($umkm['kode']);
            $proyekId = $this->terbitkanProyekUmkm($browser, $umkm, $data);
            $kandidat = $this->daftarKandidatBaru($browser, 'Kandidat PBI14');
            $this->keluar($browser);

            // Langkah 1-2: login kandidat, menu Eksplor Proyek.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaEksplor($browser);

            // Langkah 3: klik Lihat Detail & Lamar pada proyek uji.
            $this->tandaiHalamanLama($browser);
            $browser->clickAtXPath($this->xpathKartuEksplor($data['judul'])."//a[@dusk='lihat-detail']");
            $this->tungguHalamanBaru($browser)->assertPathIs("/kandidat/proyek/{$proyekId}");

            // Langkah 4: periksa isi halaman detail.
            $browser->assertSeeIn('.pd-title', $data['judul'])
                ->assertSeeIn('@status-proyek', 'Dibuka')
                ->assertSee($data['deskripsi'])
                ->assertSee('Hasil pekerjaan yang diharapkan')
                ->assertSee('Keterampilan yang dibutuhkan')
                ->assertSee(self::KET_KEMASAN)
                ->assertSee(self::KET_FIGMA)
                ->assertSee('Target penyelesaian')
                ->assertSee($this->tanggalIndonesia(now()->addDays(30)))
                ->assertSee('Kuota kandidat')
                ->assertSee('2 orang')
                ->assertSee('7 hari lagi')
                ->assertSeeIn('@kartu-umkm', 'Tentang UMKM')
                ->assertSeeIn('@kartu-umkm', $umkm['profil']['nama'])
                ->assertSeeIn('@kartu-umkm', 'Kuliner & Minuman')
                ->assertSeeIn('@kartu-umkm', 'Bandung')
                ->assertSee('Ajukan Lamaran');

            // Tiga butir hasil pekerjaan, tanpa tanda poin yang diketik UMKM.
            $butir = $browser->driver->executeScript("return [...document.querySelectorAll('.pd-list li')].map(li => li.textContent.trim());");
            $this->assertSame([
                'Desain label kemasan 3 varian siap cetak',
                'Katalog produk digital 8 halaman',
                'File sumber Figma',
            ], $butir);

            // Tabel pembayaran: Termin 1 Rp2.500.000.
            $this->assertSame([
                ['Termin 1', $data['termin'][0][1], 'Rp2.500.000', '100%'],
                ['Total', '', 'Rp2.500.000', '100%'],
            ], $this->barisTabelPembayaran($browser));

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame('published', $this->statusProyekDb($proyekId));
        });
    }

    private function barisTabelPembayaran(Browser $browser): array
    {
        return $browser->driver->executeScript(
            "return [...document.querySelectorAll('.tbl-wrap table.t tbody tr')].map(tr => [...tr.querySelectorAll('td')].map(td => td.textContent.trim()));"
        );
    }
}
