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
 * TC-PBI33-005 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Berkas DOCX (A) dan berkas 25 MB + 1 KB (B).
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
#[Group('TC-PBI33-005')]
class TC_PBI33_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-005 - Berkas DOCX (A) dan berkas 25 MB + 1 KB (B).
     */
    public function test_TC_PBI33_005_validasi_berkas_versi_perbaikan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-005');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC005');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-005');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            $this->isiFormPenyerahan($browser, $this->berkasUji('laporan.docx', 'docx', self::MB), null, 'Revisi.');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_FORMAT);

            $this->isiFormPenyerahan($browser, $this->berkasUji('besar.zip', 'zip', 25 * self::MB + 1024), null, 'Revisi.');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_UKURAN);

            $this->assertCount(1, $this->penyerahanDb($id));
            $this->assertSame([], array_values(array_filter($this->berkasPenyerahanDisk($id), fn ($p) => str_contains($p, '/v2/'))));
        });
    }
}
