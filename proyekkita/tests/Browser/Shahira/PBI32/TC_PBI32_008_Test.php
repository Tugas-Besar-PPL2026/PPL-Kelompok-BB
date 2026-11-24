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
 * TC-PBI32-008 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kesepakatan dengan batas revisi 0 kali.
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
#[Group('TC-PBI32-008')]
class TC_PBI32_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-008 - Kesepakatan dengan batas revisi 0 kali.
     */
    public function test_TC_PBI32_008_batas_revisi_nol_opsi_revisi_nonaktif(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-008');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC008');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => ['revision_limit' => 0]], 'TC32-008');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $browser->assertSeeIn('@kuota-revisi-umkm', '0 dari 0')
                ->assertSeeIn('@keterangan-revisi', 'Batas revisi sudah habis. Hasil pekerjaan hanya dapat disetujui.');
            $this->assertTrue((bool) $browser->script("return document.querySelector('[dusk=\"keputusan-revisi\"]').disabled;")[0]);
            $this->assertFalse((bool) $browser->script("return document.querySelector('[dusk=\"keputusan-setujui\"]').disabled;")[0]);
        });
    }
}
