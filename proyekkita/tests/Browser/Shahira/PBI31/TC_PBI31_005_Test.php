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
 * TC-PBI31-005 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Deskripsi pada batas bawah 1 karakter (A) dan batas atas 2000 karakter (B).
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
#[Group('TC-PBI31-005')]
class TC_PBI31_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-005 - Deskripsi pada batas bawah 1 karakter (A) dan batas atas 2000 karakter (B).
     */
    public function test_TC_PBI31_005_batas_deskripsi_1_dan_2000_karakter_diterima(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-005');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC005');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC31-005');
            $tautan = 'https://figma.com/file/abc';

            $this->masukSebagaiKandidat($browser, $kandidat);

            $this->bukaPenyerahanHasil($browser, $k['A']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan, 'A');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1)->assertSeeIn('@deskripsi-versi-1', 'A');

            $this->bukaPenyerahanHasil($browser, $k['B']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan, str_repeat('a', 2000))
                ->assertSeeIn('@hitung-deskripsi', '2000/2000');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);

            $this->assertSame('A', $this->penyerahanDb($k['A']['kesepakatan'])[0]->description);
            $this->assertSame(2000, mb_strlen($this->penyerahanDb($k['B']['kesepakatan'])[0]->description));
        });
    }
}
