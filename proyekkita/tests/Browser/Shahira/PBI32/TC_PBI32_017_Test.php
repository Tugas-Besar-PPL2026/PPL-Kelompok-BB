<?php

namespace Tests\Browser\PBI32;

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
 * TC-PBI32-017 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Riwayat dua versi dengan catatan revisi dan versi terbaru.
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
#[Group('TC-PBI32-017')]
class TC_PBI32_017_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-017 - Riwayat dua versi dengan catatan revisi dan versi terbaru.
     */
    public function test_TC_PBI32_017_riwayat_versi_tabel_dan_versi_terbaru(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-017');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC017');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-017');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];

            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap', 1);
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->kirimVersiPerbaikanLewatUi($browser, $k['Utama']['judul'], 2, null, 'https://figma.com/file/v2', 'Warna label diperbaiki.');
            $this->keluar($browser);
            $rows = $this->penyerahanDb($id);

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama'])
                ->assertSeeIn('@versi-terbaru', 'Penyerahan Versi 2')
                ->assertSeeIn('@versi-terbaru', 'Deskripsi perubahan dari kandidat')
                ->assertSeeIn('@deskripsi-terbaru', 'Warna label diperbaiki.')
                ->assertSeeIn('@status-baris-2', 'Menunggu Tinjauan')
                ->assertSeeIn('@catatan-baris-2', '–')
                ->assertSeeIn('@status-baris-1', 'Perlu Revisi')
                ->assertSeeIn('@catatan-baris-1', 'Warna label terlalu gelap')
                ->assertSeeIn('@baris-versi-1', Carbon::parse($rows[0]->submitted_at, 'UTC')->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y'))
                ->assertSeeIn('@kuota-revisi-umkm', '1 dari 2');
            $urutan = $browser->script(
                "return [...document.querySelectorAll('[dusk^=\"baris-versi-\"]')].map(e => e.getAttribute('dusk'));"
            )[0];
            $this->assertSame(['baris-versi-2', 'baris-versi-1'], $urutan);
        });
    }
}
