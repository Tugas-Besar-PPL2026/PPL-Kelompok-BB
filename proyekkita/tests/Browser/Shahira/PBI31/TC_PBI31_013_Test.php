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
 * TC-PBI31-013 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Tautan tanpa skema dilengkapi https://.
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
#[Group('TC-PBI31-013')]
class TC_PBI31_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-013 - Tautan tanpa skema dilengkapi https://.
     */
    public function test_TC_PBI31_013_tautan_tanpa_skema_dilengkapi_https(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-013');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC013');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-013');
            $diharapkan = 'https://figma.com/file/kopinusantara-label-katalog';

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->isiFormPenyerahan($browser, null, 'figma.com/file/kopinusantara-label-katalog', 'Tautan Figma final.');
            $this->kirimPenyerahan($browser)
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@tautan-versi-1', $diharapkan)
                ->assertAttribute('@tautan-versi-1', 'href', $diharapkan)
                ->assertAttribute('@tautan-versi-1', 'target', '_blank');

            $this->assertSame($diharapkan, $this->penyerahanDb($k['Utama']['kesepakatan'])[0]->result_link);
        });
    }
}
