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
 * TC-PBI32-009 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kesepakatan Dibatalkan (A) dan berubah tidak Aktif setelah halaman dibuka (B).
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
#[Group('TC-PBI32-009')]
class TC_PBI32_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-009 - Kesepakatan Dibatalkan (A) dan berubah tidak Aktif setelah halaman dibuka (B).
     */
    public function test_TC_PBI32_009_kesepakatan_tidak_aktif_menolak_keputusan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-009');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC009');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC32-009');

            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['A'], $k['B']]);
            $pesan = 'Kesepakatan berstatus Dibatalkan. Hasil pekerjaan hanya dapat ditinjau pada kesepakatan Aktif.';
            $vA = $this->penyerahanDb($k['A']['kesepakatan'])[0];
            $this->simulasikanStatusKesepakatan($k['A']['kesepakatan'], 'cancelled');

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['A'])
                ->assertMissing('@form-keputusan')
                ->assertSeeIn('@info-keputusan', $pesan);
            $this->kirimKeputusanLangsung($browser, $k['A']['kesepakatan'], [['decision', 'approve'], ['submission_id', (string) $vA->id]])
                ->waitFor('@halaman-tinjau')
                ->assertSeeIn('.al.bad', $pesan);

            $this->bukaTinjauHasil($browser, $k['B']);
            $browser->radio('decision', 'approve');
            $this->simulasikanStatusKesepakatan($k['B']['kesepakatan'], 'cancelled');
            $this->simpanKeputusanTinjau($browser, null, null)->assertSeeIn('.al.bad', $pesan);

            $this->assertSame('submitted', $this->penyerahanDb($k['A']['kesepakatan'])[0]->status);
            $this->assertSame('submitted', $this->penyerahanDb($k['B']['kesepakatan'])[0]->status);
        });
    }
}
