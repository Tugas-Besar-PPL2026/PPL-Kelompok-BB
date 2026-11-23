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
 * TC-PBI31-011 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Ukuran berkas tepat 25 MB (A) dan 25 MB + 1 KB (B).
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
#[Group('TC-PBI31-011')]
class TC_PBI31_011_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-011 - Ukuran berkas tepat 25 MB (A) dan 25 MB + 1 KB (B).
     */
    public function test_TC_PBI31_011_ukuran_berkas_25mb_diterima_dan_lebih_ditolak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-011');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC011');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-011');

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A: 26.214.400 byte (25.600 KB)
            $this->bukaPenyerahanHasil($browser, $k['A']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('besar.zip', 'zip', 25 * self::MB), null, 'Aset lengkap.');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);

            // B: 25.601 KB
            $this->bukaPenyerahanHasil($browser, $k['B']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('besar.zip', 'zip', 25 * self::MB + 1024), null, 'Aset lengkap.')
                ->assertSeeIn('@status-berkas-terpilih', 'Melebihi 25 MB');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_UKURAN);

            $this->assertCount(1, $this->penyerahanDb($k['A']['kesepakatan']));
            $this->assertCount(0, $this->penyerahanDb($k['B']['kesepakatan']));
            $this->assertSame([], $this->berkasPenyerahanDisk($k['B']['kesepakatan']));
        });
    }
}
