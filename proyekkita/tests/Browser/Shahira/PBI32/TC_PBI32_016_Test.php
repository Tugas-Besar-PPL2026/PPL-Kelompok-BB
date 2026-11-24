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
 * TC-PBI32-016 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Akses oleh kandidat, Admin, tamu, UMKM lain, dan ID tidak ada.
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
#[Group('TC-PBI32-016')]
class TC_PBI32_016_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-016 - Akses oleh kandidat, Admin, tamu, UMKM lain, dan ID tidak ada.
     */
    public function test_TC_PBI32_016_hak_akses_halaman_tinjau(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-016');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC016');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-016');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];

            $url = $this->urlTinjau($id);
            $isian = [['decision', 'approve'], ['submission_id', (string) $v1->id]];

            foreach ([
                ['masuk' => fn () => $this->masukSebagaiKandidat($browser, $kandidat), 'beranda' => '/kandidat/profil'],
                ['masuk' => fn () => $this->masukSebagaiAdmin($browser), 'beranda' => '/admin/data-master'],
            ] as $aktor) {
                ($aktor['masuk'])();
                $browser->visit($url);
                $this->assertHalamanGalat($browser, 403, 'Anda tidak memiliki akses ke halaman ini.');
                $browser->visit($aktor['beranda']);
                $this->kirimFormManipulasi($browser, 'POST', $url, $isian);
                $this->assertHalamanGalat($browser, 403, 'Anda tidak memiliki akses ke halaman ini.');
                $browser->visit($aktor['beranda']);
                $this->keluar($browser);
            }

            $browser->visit($url)->waitForLocation('/login')->assertPathIs('/login');

            $kode = $this->kodeUnik();
            $this->daftar($browser, 'umkm', "UMKM Lain Dusk {$kode}", "dusk.umkm.lain.{$kode}@proyekkita.test", self::SANDI_E2E);
            $browser->visit($url);
            $this->assertHalamanGalat($browser, 404, 'Not Found');
            $browser->visit('/umkm/profil');
            $this->keluar($browser);

            $this->masukSebagaiUmkm($browser);
            $browser->visit($this->urlTinjau($this->idTidakAda('agreements', 99999)));
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            $this->assertSame('submitted', $this->penyerahanDb($id)[0]->status);
        });
    }
}
