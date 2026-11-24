<?php

namespace Tests\Browser\PBI33;

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
 * TC-PBI33-016 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Versi 1 -> minta revisi (PBI-32) -> Versi 2 (PBI-33) -> disetujui.
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
#[Group('pbi33')]
#[Group('TC-PBI33-016')]
class TC_PBI33_016_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-016 - Versi 1 -> minta revisi (PBI-32) -> Versi 2 (PBI-33) -> disetujui.
     */
    public function test_TC_PBI33_016_siklus_penuh_revisi_hingga_disetujui(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-016');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC016');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-016');

            $id = $k['Utama']['kesepakatan'];
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Perbaiki warna.', 1);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])->assertSeeIn('@isi-catatan-revisi', 'Perbaiki warna.');
            $this->kirimVersiPerbaikanLewatUi($browser, $k['Utama']['judul'], 2, null, 'https://figma.com/file/v2', 'Warna diperbaiki.');
            $this->keluar($browser);

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);
            $this->simpanKeputusanTinjau($browser, 'approve', null)
                ->assertSeeIn('@info-keputusan', $this->pesan(self::MSG_SUDAH_DISETUJUI, ['v' => 2]));
            $this->keluar($browser);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@info-penyerahan', self::MSG_DISETUJUI)
                ->assertMissing('@form-penyerahan')
                ->assertMissing('@catatan-revisi')
                ->assertSeeIn('@status-versi-2', 'Disetujui')
                ->assertSeeIn('@status-versi-1', 'Perlu Revisi')
                ->assertSeeIn('@catatan-versi-1', 'Perbaiki warna.');

            $rows = $this->penyerahanDb($id);
            $this->assertSame(['revision_requested', 'approved'], array_map(fn ($r) => $r->status, $rows));
        });
    }
}
