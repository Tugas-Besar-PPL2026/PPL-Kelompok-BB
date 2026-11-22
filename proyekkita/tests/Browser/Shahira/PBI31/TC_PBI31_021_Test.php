<?php

namespace Tests\Browser\PBI31;

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
 * TC-PBI31-021 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Riwayat kosong dan beberapa versi dengan catatan UMKM (A); riwayat baca saja, versi tidak dapat diubah/dihapus (B).
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
#[Group('TC-PBI31-021')]
class TC_PBI31_021_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-021 - Riwayat kosong dan beberapa versi dengan catatan UMKM (A); riwayat baca saja, versi tidak dapat diubah/dihapus (B).
     */
    public function test_TC_PBI31_021_riwayat_versi_urut_dengan_catatan_dan_baca_saja(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-021');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC021');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A' => [], 'B' => [], 'C' => []], 'TC31-021');

            $this->masukSebagaiKandidat($browser, $kandidat);

            // A(i): riwayat kosong
            $this->bukaPenyerahanHasil($browser, $k['A']['judul'])
                ->assertSeeIn('@riwayat-kosong', 'Belum ada hasil pekerjaan yang diserahkan.');

            // A(ii): Versi 1 lewat UI, lalu revisi (PBI-32) dan Versi 2 (PBI-33) disimulasikan
            $this->serahkanVersi1LewatUi($browser, $k['B']['judul'], null, 'https://figma.com/file/v1', 'Versi pertama.');
            $v1 = (int) $this->penyerahanDb($k['B']['kesepakatan'])[0]->id;
            $this->simulasikanKeputusanPenyerahan($v1, 'revision_requested', 'Warna label terlalu gelap');
            $this->simulasikanVersiPerbaikan($k['B']['kesepakatan'], 2, 'https://figma.com/file/v2', 'Warna label diperbaiki.');
            $rows = $this->penyerahanDb($k['B']['kesepakatan']);

            $this->bukaPenyerahanHasil($browser, $k['B']['judul'])
                ->assertSeeIn('@status-versi-2', 'Menunggu Tinjauan')
                ->assertSeeIn('@status-versi-1', 'Perlu Revisi')
                ->assertSeeIn('@waktu-versi-1', $this->waktuVersiDiharapkan($rows[0]->submitted_at))
                ->assertSeeIn('@catatan-versi-1', 'Catatan UMKM: Warna label terlalu gelap')
                ->assertMissing('@riwayat-kosong');
            $urutan = $browser->script(
                "return [...document.querySelectorAll('[dusk=\"riwayat-versi\"] [dusk]')].map(e => e.getAttribute('dusk')).filter(d => /^versi-\\d+$/.test(d));"
            )[0];
            $this->assertSame(['versi-2', 'versi-1'], $urutan, 'Versi terbaru harus di atas.');

            // B: kesepakatan Selesai dengan Versi 1 Disetujui -> baca saja
            $this->serahkanVersi1LewatUi($browser, $k['C']['judul'], null, 'https://figma.com/file/final', 'Versi final.');
            $vc = $this->penyerahanDb($k['C']['kesepakatan'])[0];
            $this->simulasikanKeputusanPenyerahan((int) $vc->id, 'approved');
            $this->simulasikanStatusKesepakatan($k['C']['kesepakatan'], 'completed');
            $sebelum = (array) $this->penyerahanDb($k['C']['kesepakatan'])[0];

            $this->bukaPenyerahanHasil($browser, $k['C']['judul'])
                ->assertMissing('@form-penyerahan')
                ->assertSeeIn('@status-versi-1', 'Disetujui');
            $this->assertSame(0, (int) $browser->script(
                "return document.querySelectorAll('[dusk=\"riwayat-versi\"] button, [dusk=\"riwayat-versi\"] form').length;"
            )[0], 'Riwayat Versi tidak boleh memiliki tombol ubah/hapus.');

            $url = $this->urlPenyerahan($k['C']['kesepakatan']).'/'.$vc->id;
            foreach (['PUT', 'DELETE'] as $metode) {
                $this->bukaPenyerahanHasil($browser, $k['C']['judul']);
                $this->kirimFormManipulasi($browser, $metode, $url, [['description', 'Diubah.']]);
                $this->assertHalamanGalat404Atau405($browser);
            }

            $this->assertSame($sebelum, (array) $this->penyerahanDb($k['C']['kesepakatan'])[0], 'Data Versi 1 tidak boleh berubah.');
        });
    }
}
