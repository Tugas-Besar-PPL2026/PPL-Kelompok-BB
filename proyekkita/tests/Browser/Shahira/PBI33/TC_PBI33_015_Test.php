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
 * TC-PBI33-015 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Catatan revisi berisi tag script.
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
#[Group('TC-PBI33-015')]
class TC_PBI33_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-015 - Catatan revisi berisi tag script.
     */
    public function test_TC_PBI33_015_script_pada_catatan_revisi_tampil_sebagai_teks(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-015');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC015');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-015');

            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', '<script>alert(1)</script>', 1);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->assertTidakAdaDialogAlert($browser);
            $browser->assertSeeIn('@isi-catatan-revisi', '<script>alert(1)</script>')
                ->assertSeeIn('@catatan-versi-1', '<script>alert(1)</script>');
            $html = (string) $browser->script("return document.querySelector('[dusk=\"isi-catatan-revisi\"]').innerHTML;")[0];
            $this->assertStringContainsString('&lt;script&gt;', $html);
        });
    }
}
