<?php

namespace Tests\Browser\PBI32;

use Illuminate\Support\Facades\Storage;
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
 * TC-PBI32-015 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - UMKM lain, ID versi kesepakatan lain, dan berkas hilang.
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
#[Group('TC-PBI32-015')]
class TC_PBI32_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-015 - UMKM lain, ID versi kesepakatan lain, dan berkas hilang.
     */
    public function test_TC_PBI32_015_akses_berkas_umkm_tidak_berhak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-015');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC015');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC32-015');

            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['A'], $k['B']]);
            $subA = $this->penyerahanDb($k['A']['kesepakatan'])[0];
            $subB = $this->penyerahanDb($k['B']['kesepakatan'])[0];
            $urlA = $this->urlBerkasUmkm($k['A']['kesepakatan'], (int) $subA->id);

            $kode = $this->kodeUnik();
            $this->daftar($browser, 'umkm', "UMKM Lain Dusk {$kode}", "dusk.umkm.lain.{$kode}@proyekkita.test", self::SANDI_E2E);
            $browser->visit($urlA);
            $this->assertHalamanGalat($browser, 404, 'Not Found');
            $browser->visit('/umkm/profil');
            $this->keluar($browser);

            $this->masukSebagaiUmkm($browser);
            $browser->visit($this->urlBerkasUmkm($k['A']['kesepakatan'], (int) $subB->id));
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            Storage::disk('local')->delete($subA->file_path);
            $browser->visit($urlA);
            $this->assertHalamanGalat($browser, 404, self::MSG_BERKAS_TIDAK_ADA);
        });
    }
}
