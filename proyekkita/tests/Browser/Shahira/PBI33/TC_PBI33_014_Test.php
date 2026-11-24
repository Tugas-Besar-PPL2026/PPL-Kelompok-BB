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
 * TC-PBI33-014 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Database gagal saat menyimpan Versi 2.
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
#[Group('TC-PBI33-014')]
class TC_PBI33_014_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-014 - Database gagal saat menyimpan Versi 2.
     */
    public function test_TC_PBI33_014_kegagalan_simpan_versi_2_membersihkan_berkas(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-014');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC014');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-014');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            $tautan = 'https://figma.com/file/v2';
            $this->isiFormPenyerahan($browser, $this->berkasUji('katalog_v2.pdf', 'pdf'), $tautan, 'Revisi.');

            $pemicu = $this->pasangPemicuGagalSimpan($id);
            try {
                $this->kirimPenyerahan($browser)
                    ->assertSeeIn('.al.bad', self::MSG_GAGAL_SIMPAN)
                    ->assertInputValue('@tautan-hasil', $tautan)
                    ->assertInputValue('@deskripsi-hasil', 'Revisi.')
                    ->assertPresent('@catatan-revisi');
            } finally {
                $this->lepasPemicuGagalSimpan($pemicu);
            }

            $rows = $this->penyerahanDb($id);
            $this->assertCount(1, $rows);
            $this->assertSame('revision_requested', $rows[0]->status);
            $this->assertSame([], array_values(array_filter($this->berkasPenyerahanDisk($id), fn ($p) => str_contains($p, '/v2/'))));
        });
    }
}
