<?php

namespace Tests\Browser\PBI32;

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
 * TC-PBI32-011 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Versi terakhir sudah Disetujui (A) atau sedang diperbaiki kandidat (B).
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
#[Group('TC-PBI32-011')]
class TC_PBI32_011_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-011 - Versi terakhir sudah Disetujui (A) atau sedang diperbaiki kandidat (B).
     */
    public function test_TC_PBI32_011_versi_sudah_disetujui_atau_sedang_diperbaiki(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-011');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC011');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => []], 'TC32-011');

            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['A'], $k['B']]);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['A'], 'approve', null, 1);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['B'], 'revision', 'Perbaiki warna.', 1);

            $this->masukSebagaiUmkm($browser);
            foreach (['A' => self::MSG_SUDAH_DISETUJUI, 'B' => self::MSG_MENUNGGU_PERBAIKAN] as $label => $template) {
                $pesan = $this->pesan($template, ['v' => 1]);
                $versi = $this->penyerahanDb($k[$label]['kesepakatan'])[0];
                $this->bukaTinjauHasil($browser, $k[$label])
                    ->assertMissing('@form-keputusan')
                    ->assertSeeIn('@info-keputusan', $pesan);
                $this->kirimKeputusanLangsung($browser, $k[$label]['kesepakatan'], [['decision', 'approve'], ['submission_id', (string) $versi->id]])
                    ->waitFor('@halaman-tinjau')
                    ->assertSeeIn('.al.bad', $pesan);
            }

            $this->assertSame('approved', $this->penyerahanDb($k['A']['kesepakatan'])[0]->status);
            $this->assertSame('revision_requested', $this->penyerahanDb($k['B']['kesepakatan'])[0]->status);
        });
    }
}
