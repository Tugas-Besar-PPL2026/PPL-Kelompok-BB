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
 * TC-PBI32-012 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Dua keputusan dikirim berturut-turut (A) dan keputusan atas versi yang sudah berubah (B).
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
#[Group('TC-PBI32-012')]
class TC_PBI32_012_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-012 - Dua keputusan dikirim berturut-turut (A) dan keputusan atas versi yang sudah berubah (B).
     */
    public function test_TC_PBI32_012_keputusan_ganda_dan_versi_berubah_ditolak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-012');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC012');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => [], 'B' => []], 'TC32-012');

            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama'], $k['B']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];

            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);
            $hasil = $browser->driver->executeAsyncScript(<<<'JS'
                const selesai = arguments[arguments.length - 1];
                const form = document.querySelector('[dusk="form-keputusan"]');
                const kirim = (keputusan, catatan) => {
                    const data = new FormData(form);
                    data.set('decision', keputusan);
                    data.set('note', catatan);
                    return fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin'})
                        .then(async r => ({status: r.status, teks: await r.text()}));
                };
                kirim('approve', '').then(a => kirim('revision', 'Ganti warna.').then(b => selesai([a, b])))
                    .catch(e => selesai([{status: 0, teks: String(e)}, {status: 0, teks: ''}]));
            JS);
            $this->assertSame(200, (int) $hasil[0]['status']);
            $this->assertStringContainsString($this->pesan(self::MSG_SETUJU, ['v' => 1]), $hasil[0]['teks']);
            $this->assertSame(200, (int) $hasil[1]['status'], 'Kiriman kedua tidak boleh menghasilkan error server.');
            $this->assertStringContainsString($this->pesan(self::MSG_SUDAH_DISETUJUI, ['v' => 1]), $hasil[1]['teks']);
            $this->assertSame('approved', $this->penyerahanDb($id)[0]->status);
            $this->assertCount(0, $this->revisiDb((int) $v1->id));

            // B: submission_id bukan versi terakhir kesepakatan B
            $this->bukaTinjauHasil($browser, $k['B']);
            $this->kirimKeputusanLangsung($browser, $k['B']['kesepakatan'], [['decision', 'approve'], ['submission_id', (string) $v1->id]])
                ->waitFor('@halaman-tinjau')
                ->assertSeeIn('.al.bad', self::MSG_VERSI_BERUBAH);
            $this->assertSame('submitted', $this->penyerahanDb($k['B']['kesepakatan'])[0]->status);
        });
    }
}
