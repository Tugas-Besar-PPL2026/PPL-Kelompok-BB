<?php

namespace Tests\Browser\PBI31;

use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\BerkasUjiPenyerahanE2E;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Browser\Concerns\PersiapanPenyerahanE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI31-020 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Berkas tersimpan di disk privat (A) dan dapat dibuka kandidat pemilik (B).
 *
 * Alur persiapan E2E:
 *   1. Kandidat baru dibuat lewat UI registrasi (isi data diri dan keterampilan lewat UI).
 *   2. UMKM default membuat & mengajukan proyek, Admin memoderasi, kandidat melamar, UMKM menerima: semuanya lewat UI.
 *   3. Exception (dokumentasi): baris kesepakatan aktif disimulasikan di database karena PBI-24/25 belum ada
 *      (opt-in DUSK_IZINKAN_SIMULASI_DB=true). Lihat PersiapanPenyerahanE2E untuk daftar simulasi.
 *   4. Kandidat login lewat /login, membuka Lamaran Saya -> Penyerahan Hasil, lalu langkah sesuai Excel lewat Chrome.
 * Database MySQL test hanya dibaca untuk assertion tambahan, kecuali simulasi pada butir 3.
 */
#[Group('e2e-penyerahan')]
#[Group('pbi31')]
#[Group('TC-PBI31-020')]
class TC_PBI31_020_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-020 - Berkas tersimpan di disk privat (A) dan dapat dibuka kandidat pemilik (B).
     */
    public function test_TC_PBI31_020_berkas_tersimpan_di_disk_privat_dan_dapat_dibuka_pemilik(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-020');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC020');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-020');
            $idA = $k['A']['kesepakatan'];

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A: disk privat
            $this->serahkanVersi1LewatUi($browser, $k['A']['judul'], $this->berkasUji('Katalog Kopi (Final).pdf', 'pdf'), null, 'Hasil akhir.');
            $path = "submissions/{$idA}/v1/katalog-kopi-final.pdf";
            $this->assertSame($path, $this->penyerahanDb($idA)[0]->file_path);
            $this->assertTrue(Storage::disk('local')->exists($path), 'Berkas harus ada di storage/app/private.');
            $browser->visit("/storage/{$path}");
            $this->assertHalamanGalat($browser, 403, 'Forbidden');
            $browser->screenshot('tc020-setelah-403');

            // Kembali ke halaman aplikasi karena browser masih berada di halaman 403.
            $browser->visit('/kandidat/lamaran')
                ->waitForText('Pantau status setiap lamaran');

            // B: kandidat pemilik membuka PDF (inline) dan ZIP (unduhan)
            $this->serahkanVersi1LewatUi($browser, $k['B']['judul'], $this->berkasUji('label_kemasan_3sku_v1.zip', 'zip'), null, 'Hasil akhir.');
            $zip = $this->ambilLewatBrowser($browser, (string) $browser->attribute('@berkas-versi-1', 'href'));
            $this->assertSame(200, $zip['status']);
            $this->assertStringStartsWith('attachment', $zip['disposition']);
            $this->assertSame('nosniff', $zip['nosniff']);

            $this->bukaPenyerahanHasil($browser, $k['A']['judul'])->assertSeeIn('@berkas-versi-1', 'katalog-kopi-final.pdf');
            $pdf = $this->ambilLewatBrowser($browser, (string) $browser->attribute('@berkas-versi-1', 'href'));
            $this->assertSame(200, $pdf['status']);
            $this->assertStringStartsWith('inline', $pdf['disposition']);
            $this->assertSame('nosniff', $pdf['nosniff']);
            $this->assertStringContainsString('application/pdf', $pdf['type']);
        });
    }
}
