<?php

namespace Tests\Browser\PBI33;

use Carbon\Carbon;
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
 * TC-PBI33-001 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kandidat melihat banner catatan revisi dan form versi perbaikan.
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
#[Group('TC-PBI33-001')]
class TC_PBI33_001_Test extends DuskTestCase
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
     * TC-PBI33-001 - Kandidat melihat banner catatan revisi dan form versi perbaikan.
     */
    public function test_TC_PBI33_001_melihat_banner_catatan_revisi_dan_form_perbaikan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-001');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC001');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-001');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', self::CATATAN_REVISI, 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);

            $revisi = $this->revisiDb((int) $v1->id)[0];
            $waktu = Carbon::parse($revisi->created_at, 'UTC')->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y, H.i').' WIB';

            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul'])
                ->assertSeeIn('@judul-catatan-revisi', 'Permintaan revisi dari')
                ->assertSeeIn('@judul-catatan-revisi', $waktu)
                ->assertSeeIn('@isi-catatan-revisi', self::CATATAN_REVISI)
                ->assertSeeIn('@kuota-revisi', 'Revisi ke-1 dari batas 2 kali.')
                ->assertSeeIn('@judul-form-penyerahan', 'Kirim Versi Perbaikan · Versi 2')
                ->assertSee('Deskripsi perubahan')
                ->assertSeeIn('@kirim-penyerahan', 'Kirim Versi 2')
                ->assertSeeIn('@versi-disiapkan', 'Versi 2')
                ->assertSeeIn('@versi-disiapkan', 'Sedang disiapkan')
                ->assertSeeIn('@status-versi-1', 'Perlu Revisi')
                ->assertSeeIn('@catatan-versi-1', 'Catatan UMKM: '.self::CATATAN_REVISI)
                ->assertSeeIn('@status-penyerahan-terakhir', 'Perlu Revisi');
        });
    }
}
