<?php

namespace Tests\Browser\PBI31;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\BerkasUjiPenyerahanE2E;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Browser\Concerns\PersiapanPenyerahanE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI31-017 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Sudah ada Versi 1 berstatus Menunggu Tinjauan (A) atau Disetujui (B).
 *
 * Alur persiapan E2E:
 *   1. Kandidat baru dibuat lewat UI registrasi (isi data diri dan keterampilan lewat UI).
 *   2. UMKM default membuat & mengajukan proyek, Admin memoderasi, kandidat melamar, UMKM menerima: semuanya lewat UI.
 *   3. Exception (dokumentasi): baris kesepakatan aktif disimulasikan di database karena PBI-24/25 belum ada
 *      (opt-in DUSK_IZINKAN_SIMULASI_DB=true). Lihat PersiapanPenyerahanE2E untuk daftar simulasi.
 *   4. Kandidat login lewat /login, membuka Lamaran Saya -> Penyerahan Hasil, lalu langkah sesuai Excel lewat Chrome.
 * Database MySQL test hanya dibaca untuk assertion tambahan, kecuali simulasi pada butir 3.
 */
#[Group('e2e-penyerahan')]
#[Group('pbi31')]
#[Group('TC-PBI31-017')]
class TC_PBI31_017_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-017 - Sudah ada Versi 1 berstatus Menunggu Tinjauan (A) atau Disetujui (B).
     */
    public function test_TC_PBI31_017_versi_1_menunggu_tinjauan_atau_disetujui_menolak_penyerahan_baru(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-017');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC017');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-017');
            $isian = [['result_link', 'https://figma.com/file/v2'], ['description', 'Versi baru.']];

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A: Versi 1 Menunggu Tinjauan (diserahkan lewat UI)
            $this->serahkanVersi1LewatUi($browser, $k['A']['judul'], null, 'https://figma.com/file/v1', 'Versi pertama.');
            $this->bukaPenyerahanHasil($browser, $k['A']['judul'])
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@info-penyerahan', self::MSG_SEDANG_DITINJAU);
            $this->kirimPenyerahanLangsung($browser, $k['A']['kesepakatan'], $isian)
                ->waitFor('@halaman-penyerahan')
                ->assertSeeIn('.al.bad', self::MSG_TOLAK_DITINJAU);
            $this->assertCount(1, $this->penyerahanDb($k['A']['kesepakatan']));

            // B: Versi 1 Disetujui (keputusan UMKM disimulasikan; PBI-32 belum ada)
            $this->serahkanVersi1LewatUi($browser, $k['B']['judul'], null, 'https://figma.com/file/v1', 'Versi pertama.');
            $this->simulasikanKeputusanPenyerahan((int) $this->penyerahanDb($k['B']['kesepakatan'])[0]->id, 'approved');
            $this->bukaPenyerahanHasil($browser, $k['B']['judul'])
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@info-penyerahan', self::MSG_DISETUJUI);
            $this->kirimPenyerahanLangsung($browser, $k['B']['kesepakatan'], $isian)
                ->waitFor('@halaman-penyerahan')
                ->assertSeeIn('.al.bad', self::MSG_TOLAK_DISETUJUI);
            $this->assertCount(1, $this->penyerahanDb($k['B']['kesepakatan']));
        });
    }
}
