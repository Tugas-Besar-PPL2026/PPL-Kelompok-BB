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
 * TC-PBI33-009 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kesepakatan dibatalkan setelah revisi diminta (A) atau saat halaman terbuka (B).
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
#[Group('TC-PBI33-009')]
class TC_PBI33_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-009 - Kesepakatan dibatalkan setelah revisi diminta (A) atau saat halaman terbuka (B).
     */
    public function test_TC_PBI33_009_kesepakatan_dibatalkan_menolak_versi_perbaikan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-009');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC009');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC33-009');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['A'], $k['B']]);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['A'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['B'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $this->masukSebagaiKandidat($browser, $kandidat);

            $pesan = self::MSG_STATUS_KESEPAKATAN['cancelled'];
            $isian = [['result_link', 'https://figma.com/file/v2'], ['description', 'Revisi.']];

            $this->simulasikanStatusKesepakatan($k['A']['kesepakatan'], 'cancelled');
            $this->bukaPenyerahanHasil($browser, $k['A']['judul'])
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@info-penyerahan', $pesan);
            $this->kirimPenyerahanLangsung($browser, $k['A']['kesepakatan'], $isian)
                ->waitFor('@halaman-penyerahan')
                ->assertSeeIn('.al.bad', $pesan);

            $this->bukaPenyerahanHasil($browser, $k['B']['judul']);
            $this->isiFormPenyerahan($browser, null, 'https://figma.com/file/v2', 'Revisi.');
            $this->simulasikanStatusKesepakatan($k['B']['kesepakatan'], 'cancelled');
            $this->kirimPenyerahan($browser)->assertSeeIn('.al.bad', $pesan);

            $this->assertCount(1, $this->penyerahanDb($k['A']['kesepakatan']));
            $this->assertCount(1, $this->penyerahanDb($k['B']['kesepakatan']));
        });
    }
}
