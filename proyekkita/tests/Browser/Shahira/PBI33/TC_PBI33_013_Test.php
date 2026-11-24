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
 * TC-PBI33-013 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Akses oleh UMKM, Admin, kandidat lain.
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
#[Group('TC-PBI33-013')]
class TC_PBI33_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-013 - Akses oleh UMKM, Admin, kandidat lain.
     */
    public function test_TC_PBI33_013_hak_akses_versi_perbaikan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-013');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC013');
            $rina = $this->siapkanKandidatTambahan($browser, 'PBI33 Rina');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-013');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);

            $url = $this->urlPenyerahan($id);
            $isian = [['result_link', 'https://figma.com/file/v2'], ['description', 'Revisi.']];
            $this->keluar($browser);

            foreach ([
                ['masuk' => fn () => $this->masukSebagaiUmkm($browser), 'beranda' => '/umkm/profil', 'kode' => 403, 'pesan' => 'Anda tidak memiliki akses ke halaman ini.'],
                ['masuk' => fn () => $this->masukSebagaiAdmin($browser), 'beranda' => '/admin/data-master', 'kode' => 403, 'pesan' => 'Anda tidak memiliki akses ke halaman ini.'],
                ['masuk' => fn () => $this->masukSebagaiKandidat($browser, $rina), 'beranda' => '/kandidat/profil', 'kode' => 404, 'pesan' => 'Not Found'],
            ] as $aktor) {
                ($aktor['masuk'])();
                $browser->visit($url);
                $this->assertHalamanGalat($browser, $aktor['kode'], $aktor['pesan']);
                $browser->visit($aktor['beranda']);
                $this->kirimFormManipulasi($browser, 'POST', $url, $isian);
                $this->assertHalamanGalat($browser, $aktor['kode'], $aktor['pesan']);
                $browser->visit($aktor['beranda']);
                $this->keluar($browser);
            }

            $this->assertCount(1, $this->penyerahanDb($id));
        });
    }
}
