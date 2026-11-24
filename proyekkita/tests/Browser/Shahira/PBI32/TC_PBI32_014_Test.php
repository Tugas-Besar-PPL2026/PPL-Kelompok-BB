<?php

namespace Tests\Browser\PBI32;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\BerkasUjiPenyerahanE2E;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PeninjauanHasilE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Browser\Concerns\PersiapanPenyerahanE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI32-014 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - UMKM pemilik proyek membuka berkas PDF dan ZIP serta tautan hasil.
 *
 * Alur persiapan E2E:
 *   1. Kandidat baru dibuat lewat UI registrasi; UMKM default membuat & mengajukan proyek, Admin memoderasi,
 *      kandidat melamar, UMKM menerima: semuanya lewat UI.
 *   2. Exception (dokumentasi): baris kesepakatan aktif disimulasikan di database karena PBI-24/25 belum ada
 *      (opt-in DUSK_IZINKAN_SIMULASI_DB=true). Lihat PersiapanPenyerahanE2E dan PeninjauanHasilE2E.
 *   3. Penyerahan Versi 1 (PBI-31), keputusan UMKM (PBI-32), dan versi perbaikan (PBI-33) dibuat lewat UI.
 * Database MySQL test hanya dibaca untuk assertion tambahan, kecuali simulasi pada butir 2.
 */
#[Group('e2e-penyerahan')]
#[Group('pbi32')]
#[Group('TC-PBI32-014')]
class TC_PBI32_014_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-014 - UMKM pemilik proyek membuka berkas PDF dan ZIP serta tautan hasil.
     */
    public function test_TC_PBI32_014_umkm_membuka_berkas_pdf_zip_dan_tautan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-014');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC014');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => [], 'B' => []], 'TC32-014');

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->serahkanVersi1LewatUi($browser, $k['Utama']['judul'], $this->berkasUji('katalog_v1.pdf', 'pdf'), 'https://figma.com/file/katalog-v1', 'Versi pertama.');
            $this->serahkanVersi1LewatUi($browser, $k['B']['judul'], $this->berkasUji('label_kemasan_3sku_v1.zip', 'zip'), null, 'Versi pertama.');
            $this->keluar($browser);

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama'])
                ->assertAttribute('@buka-tautan-terbaru', 'href', 'https://figma.com/file/katalog-v1')
                ->assertAttribute('@buka-tautan-terbaru', 'target', '_blank');
            $pdf = $this->ambilLewatBrowser($browser, (string) $browser->attribute('@unduh-berkas-terbaru', 'href'));
            $this->assertSame(200, $pdf['status']);
            $this->assertStringStartsWith('inline', $pdf['disposition']);
            $this->assertSame('nosniff', $pdf['nosniff']);
            $this->assertStringContainsString('application/pdf', $pdf['type']);

            $this->bukaTinjauHasil($browser, $k['B']);
            $zip = $this->ambilLewatBrowser($browser, (string) $browser->attribute('@unduh-berkas-terbaru', 'href'));
            $this->assertSame(200, $zip['status']);
            $this->assertStringStartsWith('attachment', $zip['disposition']);
            $this->assertSame('nosniff', $zip['nosniff']);
        });
    }
}
