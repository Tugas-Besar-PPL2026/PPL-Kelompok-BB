<?php

namespace Tests\Browser\PBI33;

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
 * TC-PBI33-003 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Versi 2 hanya tautan (A) atau hanya berkas ZIP (B).
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
#[Group('pbi33')]
#[Group('TC-PBI33-003')]
class TC_PBI33_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-003 - Versi 2 hanya tautan (A) atau hanya berkas ZIP (B).
     */
    public function test_TC_PBI33_003_versi_2_hanya_tautan_atau_hanya_zip(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-003');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC003');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC33-003');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['A'], $k['B']]);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['A'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['B'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $this->masukSebagaiKandidat($browser, $kandidat);

            $this->kirimVersiPerbaikanLewatUi($browser, $k['A']['judul'], 2, null, 'https://figma.com/file/v2', 'Revisi warna.');
            $this->kirimVersiPerbaikanLewatUi($browser, $k['B']['judul'], 2, $this->berkasUji('label_kemasan_3sku_v2.zip', 'zip'), null, 'Aset label diperbarui.');

            $a = $this->penyerahanDb($k['A']['kesepakatan'])[1];
            $b = $this->penyerahanDb($k['B']['kesepakatan'])[1];
            $this->assertNull($a->file_path);
            $this->assertSame('https://figma.com/file/v2', $a->result_link);
            $this->assertNull($b->result_link);
            $this->assertSame("submissions/{$k['B']['kesepakatan']}/v2/label-kemasan-3sku-v2.zip", $b->file_path);
        });
    }
}
