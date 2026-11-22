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
 * TC-PBI31-018 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Kiriman ganda pada kesepakatan yang sama.
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
#[Group('TC-PBI31-018')]
class TC_PBI31_018_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-018 - Kiriman ganda pada kesepakatan yang sama.
     */
    public function test_TC_PBI31_018_kiriman_ganda_hanya_menyimpan_satu_versi(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-018');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC018');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-018');
            $id = $k['Utama']['kesepakatan'];

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->isiFormPenyerahan($browser, null, 'https://figma.com/file/abc', 'Hasil akhir.');

            // Dua kiriman berturut-turut dari form yang sama (simulasi klik ganda / dua tab).
            $hasil = $browser->driver->executeAsyncScript(<<<'JS'
                const selesai = arguments[arguments.length - 1];
                const form = document.querySelector('[dusk="form-penyerahan"]');
                const kirim = () => fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin'})
                    .then(async r => ({status: r.status, teks: await r.text()}));
                kirim().then(a => kirim().then(b => selesai([a, b])))
                    .catch(e => selesai([{status: 0, teks: String(e)}, {status: 0, teks: ''}]));
            JS);

            $this->assertSame(200, (int) $hasil[0]['status']);
            $this->assertStringContainsString(self::MSG_SUKSES_V1, $hasil[0]['teks']);
            $this->assertSame(200, (int) $hasil[1]['status'], 'Kiriman kedua tidak boleh menghasilkan error server.');
            $this->assertStringContainsString(self::MSG_TOLAK_DITINJAU, $hasil[1]['teks']);

            $browser->visit($this->urlPenyerahan($id))
                ->waitFor('@halaman-penyerahan')
                ->assertPresent('@versi-1')
                ->assertMissing('@versi-2');

            $this->assertCount(1, $this->penyerahanDb($id), 'Hanya boleh tersimpan satu versi.');
        });
    }
}
