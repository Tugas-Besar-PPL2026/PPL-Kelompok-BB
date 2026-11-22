<?php

namespace Tests\Browser\PBI31;

use Illuminate\Support\Facades\Route;
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
 * TC-PBI31-025 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Navigasi dan konsumsi data oleh PBI terkait.
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
#[Group('TC-PBI31-025')]
class TC_PBI31_025_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-025 - Navigasi dan konsumsi data oleh PBI terkait.
     */
    public function test_TC_PBI31_025_integrasi_navigasi_dan_unduhan_umkm(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-025');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC025');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-025');
            $id = $k['Utama']['kesepakatan'];

            // Navigasi kandidat ke Penyerahan Hasil. Detail kesepakatan (PBI-26) belum ada, sehingga pintu masuk
            // yang tersedia adalah tombol "Penyerahan Hasil" pada kartu Lamaran Saya.
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->serahkanVersi1LewatUi($browser, $k['Utama']['judul'], $this->berkasUji('katalog_integrasi.pdf', 'pdf'), null, 'Hasil akhir.');
            $browser->assertPathIs($this->urlPenyerahan($id));
            $sub = $this->penyerahanDb($id)[0];
            $this->keluar($browser);

            if (! Route::has('umkm.kesepakatan.penyerahan.berkas')) {
                $this->markTestIncomplete(
                    'TC-PBI31-025: bagian UMKM membutuhkan route PBI-32 "umkm.kesepakatan.penyerahan.berkas" yang belum '
                    .'diimplementasikan. Navigasi kandidat sudah diverifikasi; jalankan ulang setelah PBI-32 tersedia.'
                );
            }

            $urlUmkm = route('umkm.kesepakatan.penyerahan.berkas', [$id, $sub->id], false);

            // UMKM pemilik proyek -> 200
            $this->masukSebagaiUmkm($browser);
            $this->assertSame(200, $this->ambilLewatBrowser($browser, $urlUmkm)['status']);
            $this->keluar($browser);

            // UMKM lain -> 403/404
            $kode = $this->kodeUnik();
            $this->daftar($browser, 'umkm', "UMKM Lain Dusk {$kode}", "dusk.umkm.lain.{$kode}@proyekkita.test", self::SANDI_E2E);
            $this->assertContains($this->ambilLewatBrowser($browser, $urlUmkm)['status'], [403, 404]);
        });
    }
}
