<?php

namespace Tests\Browser\PBI32;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\BerkasUjiPenyerahanE2E;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PeninjauanHasilE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Browser\Concerns\PersiapanPenyerahanE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI32-001 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - UMKM menyetujui Versi 1 tanpa catatan.
 *
 * Alur persiapan E2E:
 *   1. Kandidat baru dibuat lewat UI registrasi; UMKM default membuat & mengajukan proyek, Admin memoderasi,
 *      kandidat melamar, UMKM menerima: semuanya lewat UI.
 *   2. Exception (dokumentasi): baris kesepakatan aktif disimulasikan di database karena PBI-24/25 belum ada
 *      (opt-in DUSK_IZINKAN_SIMULASI_DB=true). Lihat PersiapanPenyerahanE2E dan PeninjauanHasilE2E.
 *   3. Penyerahan Versi 1 (PBI-31), keputusan UMKM (PBI-32), dan versi perbaikan (PBI-33) dibuat lewat UI.
 * Database MySQL test hanya dibaca untuk assertion tambahan, kecuali simulasi pada butir 2.
 */
#[Group('e2e-penyerahan')]
#[Group('pbi32')]
#[Group('TC-PBI32-001')]
class TC_PBI32_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-001 - UMKM menyetujui Versi 1 tanpa catatan.
     */
    public function test_TC_PBI32_001_menyetujui_versi_1_tanpa_catatan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-001');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC001');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-001');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $browser->assertSeeIn('@status-versi-terbaru', 'Versi 1 · Menunggu Tinjauan')
                ->assertSeeIn('@kuota-revisi-umkm', '0 dari 2')
                ->assertSeeIn('@nama-berkas-terbaru', 'katalog-v1.pdf');

            $this->simpanKeputusanTinjau($browser, 'approve', null)
                ->assertSee($this->pesan(self::MSG_SETUJU, ['v' => 1]))
                ->assertSeeIn('@status-versi-terbaru', 'Versi 1 · Disetujui')
                ->assertSeeIn('@info-keputusan', $this->pesan(self::MSG_SUDAH_DISETUJUI, ['v' => 1]))
                ->assertMissing('@form-keputusan');

            $row = $this->penyerahanDb($id)[0];
            $this->assertSame('approved', $row->status);
            $this->assertNotNull($row->reviewed_at);
            $this->assertNull($row->review_note);
            $this->assertSame('active', DB::table('agreements')->where('id', $id)->value('status'), 'Kesepakatan tetap Aktif.');
            $this->keluar($browser);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@status-versi-1', 'Disetujui')
                ->assertSeeIn('@info-penyerahan', self::MSG_DISETUJUI);
        });
    }
}
