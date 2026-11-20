<?php

namespace Tests\Browser\Aisyah\PBI14;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI14-003 (sheet PBI 14, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM "Warung Uji PBI14 [kode]" terverifikasi membuat proyek dengan pembayaran Bertahap (2 termin)
 *              lewat form, Admin menyetujui; satu akun kandidat didaftarkan.
 *   Langkah  : login kandidat, Eksplor Proyek, periksa jumlah termin, Lihat Detail & Lamar, periksa tabel pembayaran.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi14')]
#[Group('TC-PBI14-003')]
class TC_PBI14_003_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    private const TERMIN_1 = 'Uang muka setelah kesepakatan berstatus aktif';

    private const TERMIN_2 = 'Pelunasan setelah hasil akhir disetujui UMKM';

    /**
     * TC-PBI14-003 - Detail proyek menampilkan dua termin beserta nominal dan persentasenya.
     */
    public function test_TC_PBI14_003_skema_pembayaran_bertahap(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: proyek Dipublikasikan dengan pembayaran bertahap, akun kandidat terdaftar.
            $umkm = $this->siapkanUmkmBaru($browser, 'PBI14', $this->profilWarungUji());
            $data = $this->dataProyekPbi14($umkm['kode'], [
                'termin' => [[1000000, self::TERMIN_1], [1500000, self::TERMIN_2]],
            ]);
            $proyekId = $this->terbitkanProyekUmkm($browser, $umkm, $data);
            $kandidat = $this->daftarKandidatBaru($browser, 'Kandidat PBI14');
            $this->keluar($browser);

            // Langkah 1-2: login kandidat, menu Eksplor Proyek.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaEksplor($browser);

            // Langkah 3: jumlah termin pada kartu proyek.
            $kartu = $this->teksKartuEksplor($browser, $data['judul']);
            $this->assertNotNull($kartu, 'Kartu proyek uji tampil di Eksplor Proyek.');
            $this->assertStringContainsString('Rp2.500.000 · 2 termin', $kartu);

            // Langkah 4: klik Lihat Detail & Lamar.
            $this->tandaiHalamanLama($browser);
            $browser->clickAtXPath($this->xpathKartuEksplor($data['judul'])."//a[@dusk='lihat-detail']");
            $this->tungguHalamanBaru($browser)->assertPathIs("/kandidat/proyek/{$proyekId}");

            // Langkah 5: tabel skema pembayaran.
            $baris = $browser->driver->executeScript(
                "return [...document.querySelectorAll('.tbl-wrap table.t tbody tr')].map(tr => [...tr.querySelectorAll('td')].map(td => td.textContent.trim()));"
            );
            $this->assertSame([
                ['Termin 1', self::TERMIN_1, 'Rp1.000.000', '40%'],
                ['Termin 2', self::TERMIN_2, 'Rp1.500.000', '60%'],
                ['Total', '', 'Rp2.500.000', '100%'],
            ], $baris);

            // Verifikasi tambahan di database (hanya membaca).
            $this->assertSame([1000000.0, 1500000.0], DB::table('payment_terms')
                ->where('project_id', $proyekId)->orderBy('term_number')->pluck('amount')->map(fn ($n) => (float) $n)->all());
        });
    }
}
