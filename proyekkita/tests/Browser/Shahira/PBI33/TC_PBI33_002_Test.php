<?php

namespace Tests\Browser\PBI33;

use Illuminate\Support\Facades\Storage;
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
 * TC-PBI33-002 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kandidat mengirim Versi 2 berisi berkas PDF, tautan, dan deskripsi perubahan.
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
#[Group('TC-PBI33-002')]
class TC_PBI33_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    private const CATATAN_REVISI = 'Warna label terlalu gelap, mohon dibuat lebih terang.';

    /**
     * TC-PBI33-002 - Kandidat mengirim Versi 2 berisi berkas PDF, tautan, dan deskripsi perubahan.
     */
    public function test_TC_PBI33_002_mengirim_versi_2_berkas_tautan_deskripsi(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-002');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC002');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-002');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', self::CATATAN_REVISI, 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);

            $tautan = 'https://figma.com/file/kopinusantara-label-katalog';
            $deskripsi = 'Warna label Liberika Riau dicerahkan.';
            $this->kirimVersiPerbaikanLewatUi($browser, $k['Utama']['judul'], 2, $this->berkasUji('katalog_kopi_nusantara_v2.pdf', 'pdf'), $tautan, $deskripsi);

            $browser->assertMissing('@catatan-revisi')
                ->assertSeeIn('@info-penyerahan', 'Versi 2 sedang ditinjau UMKM.')
                ->assertSeeIn('@status-versi-2', 'Menunggu Tinjauan')
                ->assertSeeIn('@berkas-versi-2', 'katalog-kopi-nusantara-v2.pdf')
                ->assertSeeIn('@deskripsi-versi-2', $deskripsi)
                ->assertSeeIn('@status-versi-1', 'Perlu Revisi')
                ->assertSeeIn('@catatan-versi-1', self::CATATAN_REVISI);
            $urutan = $browser->script(
                "return [...document.querySelectorAll('[dusk=\"riwayat-versi\"] [dusk]')].map(e => e.getAttribute('dusk')).filter(d => /^versi-\\d+$/.test(d));"
            )[0];
            $this->assertSame(['versi-2', 'versi-1'], $urutan);

            $rows = $this->penyerahanDb($id);
            $this->assertCount(2, $rows);
            $this->assertSame(2, (int) $rows[1]->version_number);
            $this->assertSame('submitted', $rows[1]->status);
            $this->assertSame("submissions/{$id}/v2/katalog-kopi-nusantara-v2.pdf", $rows[1]->file_path);
            $this->assertTrue(Storage::disk('local')->exists($rows[1]->file_path));
            $this->assertSame((array) $v1, (array) $rows[0], 'Versi 1 tidak boleh berubah.');
        });
    }
}
