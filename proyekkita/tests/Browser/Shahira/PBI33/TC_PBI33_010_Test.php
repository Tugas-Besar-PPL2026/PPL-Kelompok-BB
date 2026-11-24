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
 * TC-PBI33-010 (sheet PBI-33, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Dua kiriman Versi 2 berturut-turut.
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
#[Group('TC-PBI33-010')]
class TC_PBI33_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI33-010 - Dua kiriman Versi 2 berturut-turut.
     */
    public function test_TC_PBI33_010_kiriman_ganda_versi_2_hanya_satu(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI33-010');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI33 TC010');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC33-010');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $this->umkmMemutuskanHasilLewatUi($browser, $k['Utama'], 'revision', 'Warna label terlalu gelap, mohon dibuat lebih terang.', 1);
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            $this->isiFormPenyerahan($browser, null, 'https://figma.com/file/v2', 'Revisi warna.');
            $hasil = $browser->driver->executeAsyncScript(<<<'JS'
                const selesai = arguments[arguments.length - 1];
                const form = document.querySelector('[dusk="form-penyerahan"]');
                const kirim = () => fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin'})
                    .then(async r => ({status: r.status, teks: await r.text()}));
                kirim().then(a => kirim().then(b => selesai([a, b])))
                    .catch(e => selesai([{status: 0, teks: String(e)}, {status: 0, teks: ''}]));
            JS);
            $this->assertSame(200, (int) $hasil[0]['status']);
            $this->assertStringContainsString('Hasil pekerjaan Versi 2 berhasil diserahkan dan berstatus Menunggu Tinjauan.', $hasil[0]['teks']);
            $this->assertSame(200, (int) $hasil[1]['status']);
            $this->assertStringContainsString('Hasil pekerjaan Versi 2 sedang ditinjau UMKM. Versi baru belum dapat dikirim.', $hasil[1]['teks']);
            $this->assertCount(2, $this->penyerahanDb($id));
        });
    }
}
