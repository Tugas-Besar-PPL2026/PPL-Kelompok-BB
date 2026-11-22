<?php

namespace Tests\Browser\PBI31;

use Carbon\Carbon;
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
 * TC-PBI31-016 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Tenggat kesepakatan sudah lewat dan progres belum 100%.
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
#[Group('TC-PBI31-016')]
class TC_PBI31_016_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-016 - Tenggat kesepakatan sudah lewat dan progres belum 100%.
     */
    public function test_TC_PBI31_016_tenggat_lewat_dan_progres_belum_100_tetap_diterima(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-016');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC016');
            $kemarin = Carbon::yesterday();
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => ['deadline' => $kemarin->toDateString()]], 'TC31-016');
            $this->simulasikanProgres($k['Utama']['kesepakatan'], 60);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@tenggat-lewat', 'Melewati tenggat '.$this->tanggalIndonesia($kemarin))
                ->assertSeeIn('@tahapan', 'Progres 60%');

            $this->isiFormPenyerahan($browser, null, 'https://figma.com/file/abc', 'Hasil akhir.');
            $this->kirimPenyerahan($browser)
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@status-versi-1', 'Menunggu Tinjauan');

            $this->assertCount(1, $this->penyerahanDb($k['Utama']['kesepakatan']));
        });
    }
}
