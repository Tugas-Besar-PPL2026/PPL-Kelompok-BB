<?php

namespace Tests\Browser\PBI31;

use Illuminate\Support\Facades\Storage;
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
 * TC-PBI31-022 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Akses route oleh UMKM, Admin, tamu, kandidat lain, ID tidak ada (A); akses berkas oleh pihak tidak berhak (B).
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
#[Group('TC-PBI31-022')]
class TC_PBI31_022_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-022 - Akses route oleh UMKM, Admin, tamu, kandidat lain, ID tidak ada (A); akses berkas oleh pihak tidak berhak (B).
     */
    public function test_TC_PBI31_022_hak_akses_halaman_dan_berkas_penyerahan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-022');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC022');
            $rina = $this->siapkanKandidatTambahan($browser, 'PBI31 Rina');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['A1' => [], 'A2' => []], 'TC31-022');
            $id1 = $k['A1']['kesepakatan'];
            $id2 = $k['A2']['kesepakatan'];
            $url = $this->urlPenyerahan($id1);
            $isian = [['result_link', 'https://figma.com/file/abc'], ['description', 'Hasil akhir.']];

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->serahkanVersi1LewatUi($browser, $k['A1']['judul'], $this->berkasUji('katalog_a1.pdf', 'pdf'), null, 'Hasil A1.');
            $this->serahkanVersi1LewatUi($browser, $k['A2']['judul'], $this->berkasUji('katalog_a2.pdf', 'pdf'), null, 'Hasil A2.');
            $sub1 = $this->penyerahanDb($id1)[0];
            $sub2 = $this->penyerahanDb($id2)[0];
            $this->keluar($browser);

            // A1-A2: UMKM dan Admin -> 403 (GET dan POST)
            foreach ([
                ['masuk' => fn () => $this->masukSebagaiUmkm($browser), 'beranda' => '/umkm/profil'],
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

            // A3: tamu -> login
            $browser->visit($url)->waitForLocation('/login')->assertPathIs('/login');

            // A4 & B1: kandidat lain -> 404 untuk halaman dan berkas
            $this->masukSebagaiKandidat($browser, $rina);
            $browser->visit($url);
            $this->assertHalamanGalat($browser, 404, 'Not Found');
            $browser->visit($this->urlBerkas($id1, (int) $sub1->id));
            $this->assertHalamanGalat($browser, 404, 'Not Found');
            $browser->visit('/kandidat/profil');
            $this->keluar($browser);

            // A5: ID kesepakatan tidak ada; B2: ID versi milik kesepakatan lain
            $this->masukSebagaiKandidat($browser, $kandidat);
            $browser->visit($this->urlPenyerahan($this->idTidakAda('agreements', 99999)));
            $this->assertHalamanGalat($browser, 404, 'Not Found');
            $browser->visit($this->urlBerkas($id1, (int) $sub2->id));
            $this->assertHalamanGalat($browser, 404, 'Not Found');

            // B3: berkas hilang dari disk (simulasi kehilangan berkas penyimpanan)
            Storage::disk('local')->delete($sub1->file_path);
            $browser->visit($this->urlBerkas($id1, (int) $sub1->id));
            $this->assertHalamanGalat($browser, 404, self::MSG_BERKAS_TIDAK_ADA);
            $browser->visit($url)->waitFor('@halaman-penyerahan')->assertPresent('@berkas-hilang-1');

            $this->assertCount(1, $this->penyerahanDb($id1));
            $this->assertCount(1, $this->penyerahanDb($id2));
        });
    }
}
