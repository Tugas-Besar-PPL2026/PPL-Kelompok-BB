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
 * TC-PBI32-005 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Minta revisi dengan catatan kosong (A) atau hanya spasi (B).
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
#[Group('TC-PBI32-005')]
class TC_PBI32_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-005 - Minta revisi dengan catatan kosong (A) atau hanya spasi (B).
     */
    public function test_TC_PBI32_005_catatan_revisi_kosong_atau_spasi_ditolak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-005');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC005');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-005');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            foreach (['', '     '] as $catatan) {
                $this->simpanKeputusanTinjau($browser, 'revision', $catatan)
                    ->assertSeeIn('@error-catatan', self::MSG_CATATAN_WAJIB);
            }
            $this->assertCount(0, $this->revisiDb((int) $v1->id));
            $this->assertSame('submitted', $this->penyerahanDb($id)[0]->status);
        });
    }
}
