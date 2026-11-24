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
 * TC-PBI33-008 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Versi 2 sudah dikirim dan sedang ditinjau.
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
#[Group('TC-PBI33-008')]
class TC_PBI33_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-008 - Versi 2 sudah dikirim dan sedang ditinjau.
     */
    public function test_TC_PBI33_008_versi_2_sedang_ditinjau_menolak_versi_baru(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-008');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC008');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-008');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);

            $this->kirimVersiPerbaikanLewatUi($browser, $k['Utama']['judul'], 2, null, 'https://figma.com/file/v2', 'Revisi warna.');
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertMissing('@catatan-revisi')
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@info-penyerahan', 'Versi 2 sedang ditinjau UMKM.');
            $this->kirimPenyerahanLangsung($browser, $id, [['result_link', 'https://figma.com/file/v3'], ['description', 'Versi lain.']])
                ->waitFor('@halaman-penyerahan')
                ->assertSeeIn('.al.bad', 'Hasil pekerjaan Versi 2 sedang ditinjau UMKM. Versi baru belum dapat dikirim.');
            $this->assertCount(2, $this->penyerahanDb($id));
        });
    }
}
