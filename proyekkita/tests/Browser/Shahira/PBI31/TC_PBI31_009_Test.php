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
 * TC-PBI31-009 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Format JPG dan PNG diterima.
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
#[Group('TC-PBI31-009')]
class TC_PBI31_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-009 - Format JPG dan PNG diterima.
     */
    public function test_TC_PBI31_009_format_jpg_dan_png_diterima(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-009');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC009');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['JPG' => [], 'PNG' => []], 'TC31-009');
            $deskripsi = 'Mockup label final.';

            $this->masukSebagaiKandidat($browser, $kandidat);

            $this->bukaPenyerahanHasil($browser, $k['JPG']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('mockup_label.jpg', 'jpg'), null, $deskripsi);
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1)->assertSeeIn('@berkas-versi-1', 'mockup-label.jpg');

            $this->bukaPenyerahanHasil($browser, $k['PNG']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('mockup_label.png', 'png'), null, $deskripsi);
            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1)->assertSeeIn('@berkas-versi-1', 'mockup-label.png');

            $this->assertCount(1, $this->penyerahanDb($k['JPG']['kesepakatan']));
            $this->assertCount(1, $this->penyerahanDb($k['PNG']['kesepakatan']));
        });
    }
}
