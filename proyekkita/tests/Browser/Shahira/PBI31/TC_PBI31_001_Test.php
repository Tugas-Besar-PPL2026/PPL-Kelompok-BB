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
 * TC-PBI31-001 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kandidat menyerahkan Versi 1 berisi berkas PDF, tautan, dan deskripsi valid.
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
#[Group('TC-PBI31-001')]
class TC_PBI31_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-001 - Kandidat menyerahkan Versi 1 berisi berkas PDF, tautan, dan deskripsi valid.
     */
    public function test_TC_PBI31_001_menyerahkan_versi_1_berkas_pdf_tautan_deskripsi(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-001');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC001');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-001');
            $id = $k['Utama']['kesepakatan'];
            $pdf = $this->berkasUji('katalog_kopi_nusantara_v1.pdf', 'pdf', (int) round(8.4 * self::MB));
            $tautan = 'https://figma.com/file/kopinusantara-label-katalog';
            $deskripsi = 'Katalog 12 halaman dan label 3 SKU sesuai brief.';

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@judul-form-penyerahan', 'Kirim Hasil Pekerjaan · Versi 1')
                ->assertSeeIn('@riwayat-kosong', 'Belum ada hasil pekerjaan yang diserahkan.');

            $this->isiFormPenyerahan($browser, $pdf, $tautan, $deskripsi)
                ->assertSeeIn('@status-berkas-terpilih', 'valid');

            $this->kirimPenyerahan($browser)
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@info-penyerahan', self::MSG_SEDANG_DITINJAU)
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@status-versi-1', 'Menunggu Tinjauan')
                ->assertSeeIn('@berkas-versi-1', 'katalog-kopi-nusantara-v1.pdf')
                ->assertSeeIn('@tautan-versi-1', $tautan)
                ->assertSeeIn('@deskripsi-versi-1', $deskripsi);

            $rows = $this->penyerahanDb($id);
            $this->assertCount(1, $rows, 'Harus tersimpan tepat 1 penyerahan.');
            $this->assertSame(1, (int) $rows[0]->version_number);
            $this->assertSame('submitted', $rows[0]->status);
            $this->assertNotNull($rows[0]->submitted_at);
            $this->assertSame("submissions/{$id}/v1/katalog-kopi-nusantara-v1.pdf", $rows[0]->file_path);
            $this->assertSame($tautan, $rows[0]->result_link);
            $this->assertSame($deskripsi, $rows[0]->description);
            $this->assertTrue(Storage::disk('local')->exists($rows[0]->file_path), 'Berkas tidak tersimpan di disk privat.');
            $browser->assertSeeIn('@waktu-versi-1', $this->waktuVersiDiharapkan($rows[0]->submitted_at));
        });
    }
}
