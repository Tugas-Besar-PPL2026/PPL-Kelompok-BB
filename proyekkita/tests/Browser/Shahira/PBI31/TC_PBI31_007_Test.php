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
 * TC-PBI31-007 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Spasi tepi dibuang (A) dan deskripsi multibyte dihitung per karakter (B).
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
#[Group('TC-PBI31-007')]
class TC_PBI31_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-007 - Spasi tepi dibuang (A) dan deskripsi multibyte dihitung per karakter (B).
     */
    public function test_TC_PBI31_007_spasi_tepi_dibuang_dan_multibyte_dihitung_per_karakter(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-007');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC007');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B1' => [], 'B2' => []], 'TC31-007');
            $tautan = 'https://figma.com/file/abc';

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A: spasi tepi dibuang
            $this->bukaPenyerahanHasil($browser, $k['A']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan, '   Katalog final   ');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1)->assertSeeIn('@deskripsi-versi-1', 'Katalog final');
            $this->assertSame('Katalog final', $this->penyerahanDb($k['A']['kesepakatan'])[0]->description);

            // B1: 2000 emoji (8000 byte) diterima
            $this->bukaPenyerahanHasil($browser, $k['B1']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan, str_repeat('😀', 2000))
                ->assertSeeIn('@hitung-deskripsi', '2000/2000');
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);
            $this->assertSame(2000, mb_strlen($this->penyerahanDb($k['B1']['kesepakatan'])[0]->description));

            // B2: 2001 karakter Katakana ditolak
            $this->bukaPenyerahanHasil($browser, $k['B2']['judul']);
            $this->isiFormPenyerahan($browser, null, $tautan, str_repeat('ア', 2001));
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-deskripsi', self::MSG_DESKRIPSI_MAKS);
            $this->assertCount(0, $this->penyerahanDb($k['B2']['kesepakatan']));
        });
    }
}
