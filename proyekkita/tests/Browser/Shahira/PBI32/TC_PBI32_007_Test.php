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
 * TC-PBI32-007 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Batas revisi sudah habis (revisi terpakai 1 dari 1).
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
#[Group('TC-PBI32-007')]
class TC_PBI32_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-007 - Batas revisi sudah habis (revisi terpakai 1 dari 1).
     */
    public function test_TC_PBI32_007_batas_revisi_habis_hanya_dapat_menyetujui(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-007');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC007');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => ['revision_limit' => 1]], 'TC32-007');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];

            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap.', 1);
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->kirimVersiPerbaikanLewatUi($browser, $k['Utama']['judul'], 2, null, 'https://figma.com/file/v2', 'Warna diperbaiki.');
            $this->keluar($browser);
            $v2 = $this->penyerahanDb($id)[1];

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama'])
                ->assertSeeIn('@kuota-revisi-umkm', '1 dari 1')
                ->assertSeeIn('@keterangan-revisi', 'Batas revisi sudah habis. Hasil pekerjaan hanya dapat disetujui.');
            $this->assertTrue((bool) $browser->script("return document.querySelector('[dusk=\"keputusan-revisi\"]').disabled;")[0]);

            $this->kirimKeputusanLangsung($browser, $id, [['decision', 'revision'], ['note', 'Masih kurang terang.'], ['submission_id', (string) $v2->id]])
                ->waitFor('@halaman-tinjau')
                ->assertSeeIn('.al.bad', $this->pesan(self::MSG_BATAS_REVISI, ['used' => 1, 'limit' => 1]));
            $this->assertSame('submitted', $this->penyerahanDb($id)[1]->status);

            $this->simpanKeputusanTinjau($browser, 'approve', null)->assertSee($this->pesan(self::MSG_SETUJU, ['v' => 2]));
            $this->assertSame('approved', $this->penyerahanDb($id)[1]->status);
            $this->assertCount(0, $this->revisiDb((int) $v2->id));
        });
    }
}
