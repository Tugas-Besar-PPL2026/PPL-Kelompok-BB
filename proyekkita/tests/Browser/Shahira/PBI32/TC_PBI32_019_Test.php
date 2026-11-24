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
 * TC-PBI32-019 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Tombol 'Tinjau Hasil' pada detail pelamar.
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
#[Group('TC-PBI32-019')]
class TC_PBI32_019_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-019 - Tombol 'Tinjau Hasil' pada detail pelamar.
     */
    public function test_TC_PBI32_019_tombol_tinjau_hasil_di_detail_pelamar(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-019');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC019');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-019');

            // Proyek kedua: kandidat diterima lewat UI tetapi belum memiliki kesepakatan.
            $judulTanpa = 'Dusk TC32-019 TanpaKesepakatan '.$this->kodeUnik();
            $proyekTanpa = $this->terbitkanProyek($browser, [$judulTanpa])[$judulTanpa];
            $this->kandidatMelamarLewatUi($browser, $kandidat, [$judulTanpa]);
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [[$proyekTanpa, 'accept', null]]);
            $lamaranTanpa = (int) $this->lamaranDb($kandidat['email'], $proyekTanpa)->id;

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);
            $aktif = $browser->script("return [...document.querySelectorAll('a.mi.on')].map(a => a.textContent.trim());")[0];
            $this->assertContains('Kesepakatan & Progres', $aktif);

            $this->bukaSeleksiProyek($browser, $proyekTanpa);
            $this->tandaiHalamanLama($browser);
            $browser->click("@lihat-pelamar-{$lamaranTanpa}");
            $this->tungguHalamanBaru($browser)->waitForText('Detail Pelamar')->assertMissing("@tinjau-hasil-{$lamaranTanpa}");
        });
    }
}
