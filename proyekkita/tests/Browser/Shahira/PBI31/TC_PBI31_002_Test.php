<?php

namespace Tests\Browser\PBI31;

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
 * TC-PBI31-002 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Penyerahan hanya tautan (A) atau hanya berkas ZIP (B).
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
#[Group('TC-PBI31-002')]
class TC_PBI31_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-002 - Penyerahan hanya tautan (A) atau hanya berkas ZIP (B).
     */
    public function test_TC_PBI31_002_penyerahan_hanya_tautan_atau_hanya_berkas_zip(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-002');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC002');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-002');
            $tautanA = 'https://drive.google.com/drive/folders/katalog-v1';
            $zip = $this->berkasUji('label_kemasan_3sku_v1.zip', 'zip', (int) round(22.1 * self::MB));

            $this->masukSebagaiKandidat($browser, $kandidat);

            // Skenario A: hanya tautan
            $this->bukaPenyerahanHasil($browser, $k['A']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautanA, 'Hasil di folder Drive.');
            $this->kirimPenyerahan($browser)
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@tautan-versi-1', $tautanA)
                ->assertMissing('@berkas-versi-1');

            // Skenario B: hanya berkas ZIP
            $this->bukaPenyerahanHasil($browser, $k['B']['judul']);
            $this->isiFormPenyerahan($browser, $zip, null, 'Aset label 3 SKU (AI + PNG).');
            $this->kirimPenyerahan($browser)
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@berkas-versi-1', 'label-kemasan-3sku-v1.zip')
                ->assertMissing('@tautan-versi-1');

            $a = $this->penyerahanDb($k['A']['kesepakatan']);
            $b = $this->penyerahanDb($k['B']['kesepakatan']);
            $this->assertCount(1, $a);
            $this->assertNull($a[0]->file_path);
            $this->assertSame($tautanA, $a[0]->result_link);
            $this->assertCount(1, $b);
            $this->assertNull($b[0]->result_link);
            $this->assertSame("submissions/{$k['B']['kesepakatan']}/v1/label-kemasan-3sku-v1.zip", $b[0]->file_path);
        });
    }
}
