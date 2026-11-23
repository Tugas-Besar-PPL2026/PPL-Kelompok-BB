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
 * TC-PBI31-015 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kesepakatan tidak Aktif (A) dan berubah tidak Aktif setelah halaman dibuka (B).
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
#[Group('TC-PBI31-015')]
class TC_PBI31_015_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-015 - Kesepakatan tidak Aktif (A) dan berubah tidak Aktif setelah halaman dibuka (B).
     */
    public function test_TC_PBI31_015_kesepakatan_tidak_aktif_dan_berubah_saat_halaman_terbuka(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-015');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC015');
            $statusTidakAktif = ['draft', 'sent', 'rejected', 'completed', 'cancelled'];
            $rencana = [];
            foreach ($statusTidakAktif as $status) {
                $rencana[$status] = ['status' => $status];
            }
            $rencana['aktif'] = [];
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, $rencana, 'TC31-015');
            $isian = [['result_link', 'https://figma.com/file/abc'], ['description', 'Hasil akhir.']];

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A: form tidak tampil; request langsung ditolak dengan pesan sesuai status
            foreach ($statusTidakAktif as $status) {
                $this->bukaPenyerahanHasil($browser, $k[$status]['judul'])
                    ->assertMissing('@form-penyerahan')
                    ->assertSeeIn('@info-penyerahan', self::MSG_STATUS_KESEPAKATAN[$status]);

                $this->kirimPenyerahanLangsung($browser, $k[$status]['kesepakatan'], $isian)
                    ->waitFor('@halaman-penyerahan')
                    ->assertSeeIn('.al.bad', self::MSG_STATUS_KESEPAKATAN[$status]);

                $this->assertCount(0, $this->penyerahanDb($k[$status]['kesepakatan']), "Status {$status}: tidak boleh tersimpan.");
            }

            // B: dibatalkan setelah halaman dibuka
            $this->bukaPenyerahanHasil($browser, $k['aktif']['judul']);
            $this->isiFormPenyerahan($browser, null, 'https://figma.com/file/abc', 'Hasil akhir.');
            $this->simulasikanStatusKesepakatan($k['aktif']['kesepakatan'], 'cancelled');
            $this->kirimPenyerahan($browser)->assertSeeIn('.al.bad', self::MSG_STATUS_KESEPAKATAN['cancelled']);

            $this->assertCount(0, $this->penyerahanDb($k['aktif']['kesepakatan']));
        });
    }
}
