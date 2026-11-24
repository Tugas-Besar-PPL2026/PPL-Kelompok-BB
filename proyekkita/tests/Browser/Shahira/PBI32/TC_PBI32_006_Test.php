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
 * TC-PBI32-006 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Catatan revisi 2001 karakter ditolak (A) dan 2000 karakter diterima (B).
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
#[Group('TC-PBI32-006')]
class TC_PBI32_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-006 - Catatan revisi 2001 karakter ditolak (A) dan 2000 karakter diterima (B).
     */
    public function test_TC_PBI32_006_batas_catatan_2001_ditolak_2000_diterima(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-006');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC006');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-006');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $this->simpanKeputusanTinjau($browser, 'revision', str_repeat('a', 2001))
                ->assertSeeIn('@error-catatan', self::MSG_CATATAN_MAKS)
                ->assertSeeIn('@hitung-catatan', '2001/2000');
            $this->assertCount(0, $this->revisiDb((int) $v1->id));

            $this->simpanKeputusanTinjau($browser, 'revision', str_repeat('a', 2000))
                ->assertSee($this->pesan(self::MSG_REVISI_TERKIRIM, ['v' => 1]));
            $this->assertSame(2000, mb_strlen($this->revisiDb((int) $v1->id)[0]->note));
        });
    }
}
