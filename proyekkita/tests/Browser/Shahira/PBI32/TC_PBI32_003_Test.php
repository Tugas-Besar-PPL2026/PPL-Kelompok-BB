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
 * TC-PBI32-003 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - UMKM meminta revisi Versi 1 dengan catatan perbaikan.
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
#[Group('TC-PBI32-003')]
class TC_PBI32_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-003 - UMKM meminta revisi Versi 1 dengan catatan perbaikan.
     */
    public function test_TC_PBI32_003_meminta_revisi_versi_1_dengan_catatan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-003');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC003');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-003');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $catatan = 'Warna label terlalu gelap, mohon dibuat lebih terang.';
            $browser->assertSeeIn('@keterangan-revisi', 'Sisa 2 kali revisi');
            $this->simpanKeputusanTinjau($browser, 'revision', $catatan)
                ->assertSee($this->pesan(self::MSG_REVISI_TERKIRIM, ['v' => 1]))
                ->assertSeeIn('@kuota-revisi-umkm', '1 dari 2')
                ->assertSeeIn('@info-keputusan', $this->pesan(self::MSG_MENUNGGU_PERBAIKAN, ['v' => 1]));

            $row = $this->penyerahanDb($id)[0];
            $this->assertSame('revision_requested', $row->status);
            $this->assertNotNull($row->reviewed_at);
            $revisi = $this->revisiDb((int) $v1->id);
            $this->assertCount(1, $revisi);
            $this->assertSame($catatan, $revisi[0]->note);
            $this->keluar($browser);

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@catatan-revisi', 'Permintaan revisi dari')
                ->assertSeeIn('@judul-form-penyerahan', 'Kirim Versi Perbaikan · Versi 2');
        });
    }
}
