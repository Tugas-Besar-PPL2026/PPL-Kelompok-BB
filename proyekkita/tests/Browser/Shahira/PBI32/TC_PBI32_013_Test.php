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
 * TC-PBI32-013 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Nilai keputusan tidak dikenal dan field tambahan.
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
#[Group('TC-PBI32-013')]
class TC_PBI32_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-013 - Nilai keputusan tidak dikenal dan field tambahan.
     */
    public function test_TC_PBI32_013_keputusan_tidak_dikenal_dan_field_tambahan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-013');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC013');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-013');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $this->kirimKeputusanLangsung($browser, $id, [['decision', 'approved'], ['submission_id', (string) $v1->id]])
                ->waitFor('@halaman-tinjau')
                ->assertSeeIn('@error-keputusan', self::MSG_PILIH_KEPUTUSAN);
            $this->assertSame('submitted', $this->penyerahanDb($id)[0]->status);

            $this->kirimKeputusanLangsung($browser, $id, [
                ['decision', 'approve'],
                ['submission_id', (string) $v1->id],
                ['status', 'revision_requested'],
                ['version_number', '9'],
                ['agreement_id', '20'],
            ])->waitFor('@halaman-tinjau')->assertSee($this->pesan(self::MSG_SETUJU, ['v' => 1]));

            $rows = $this->penyerahanDb($id);
            $this->assertCount(1, $rows);
            $this->assertSame('approved', $rows[0]->status);
            $this->assertSame(1, (int) $rows[0]->version_number);
            $this->assertCount(0, $this->revisiDb((int) $v1->id));
        });
    }
}
