<?php

namespace Tests\Browser\PBI31;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
 * TC-PBI31-019 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Manipulasi isian request.
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
#[Group('TC-PBI31-019')]
class TC_PBI31_019_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-019 - Manipulasi isian request.
     */
    public function test_TC_PBI31_019_manipulasi_isian_request_diabaikan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-019');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC019');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-019');
            $id = $k['Utama']['kesepakatan'];
            $jumlahDiKesepakatan20 = DB::table('submissions')->where('agreement_id', 20)->count();
            $mulai = Carbon::now('UTC')->subMinute();

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->kirimPenyerahanLangsung($browser, $id, [
                ['result_link', 'https://figma.com/file/abc'],
                ['description', 'Hasil akhir.'],
                ['version_number', '5'],
                ['status', 'approved'],
                ['agreement_id', '20'],
                ['submitted_at', '2020-01-01'],
                ['file_path', '../../.env'],
            ])
                ->waitFor('@halaman-penyerahan')
                ->assertSee(self::MSG_SUKSES_V1)
                ->assertSeeIn('@status-versi-1', 'Menunggu Tinjauan');

            $rows = $this->penyerahanDb($id);
            $this->assertCount(1, $rows);
            $this->assertSame(1, (int) $rows[0]->version_number);
            $this->assertSame('submitted', $rows[0]->status);
            $this->assertNull($rows[0]->file_path);
            $this->assertTrue(Carbon::parse($rows[0]->submitted_at, 'UTC')->gte($mulai), 'submitted_at harus waktu server.');
            $this->assertSame($jumlahDiKesepakatan20, DB::table('submissions')->where('agreement_id', 20)->count());
        });
    }
}
