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
 * TC-PBI31-014 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Tautan skema berbahaya/non-http (A) dan batas panjang 255 vs 256 karakter (B).
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
#[Group('TC-PBI31-014')]
class TC_PBI31_014_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-014 - Tautan skema berbahaya/non-http (A) dan batas panjang 255 vs 256 karakter (B).
     */
    public function test_TC_PBI31_014_tautan_skema_berbahaya_dan_batas_255_karakter(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-014');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC014');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-014');

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['A']['judul']);

            foreach (['javascript:alert(1)', 'ftp://files.kopi.id/katalog.zip'] as $tautan) {
                $this->isiFormPenyerahan($browser, null, $tautan, 'Hasil akhir.');
                $this->kirimPenyerahan($browser)->assertSeeIn('@error-tautan', self::MSG_TAUTAN_TIDAK_VALID);
                $this->assertTidakAdaDialogAlert($browser);
            }

            // B1: 256 karakter ditolak
            $this->isiFormPenyerahan($browser, null, 'https://a.id/'.str_repeat('a', 243), 'Hasil akhir.');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-tautan', self::MSG_TAUTAN_MAKS);
            $this->assertCount(0, $this->penyerahanDb($k['A']['kesepakatan']));

            // B2: 255 karakter diterima
            $tautan255 = 'https://a.id/'.str_repeat('a', 242);
            $this->assertSame(255, strlen($tautan255));
            $this->bukaPenyerahanHasil($browser, $k['B']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan255, 'Hasil akhir.');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);
            $this->assertSame($tautan255, $this->penyerahanDb($k['B']['kesepakatan'])[0]->result_link);
        });
    }
}
