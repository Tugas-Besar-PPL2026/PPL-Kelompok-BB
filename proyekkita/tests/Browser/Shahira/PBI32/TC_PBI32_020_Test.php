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
 * TC-PBI32-020 (sheet PBI-32, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Catatan dipertahankan setelah galat, penghitung catatan, dan tombol 'Menyimpan…'.
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
#[Group('TC-PBI32-020')]
class TC_PBI32_020_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PeninjauanHasilE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI32-020 - Catatan dipertahankan setelah galat, penghitung catatan, dan tombol 'Menyimpan…'.
     */
    public function test_TC_PBI32_020_umpan_balik_form_keputusan(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI32-020');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI32 TC020');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC32-020');
            $this->serahkanVersi1UntukKesepakatan($browser, $kandidat, [$k['Utama']]);
            $id = $k['Utama']['kesepakatan'];
            $v1 = $this->penyerahanDb($id)[0];
            $this->masukSebagaiUmkm($browser);
            $this->bukaTinjauHasil($browser, $k['Utama']);

            $this->simpanKeputusanTinjau($browser, 'revision', str_repeat('a', 2001))
                ->assertSeeIn('@error-catatan', self::MSG_CATATAN_MAKS)
                ->assertSeeIn('@hitung-catatan', '2001/2000');
            $this->assertTrue((bool) $browser->script("return document.querySelector('[dusk=\"keputusan-revisi\"]').checked;")[0]);
            $this->assertSame(2001, (int) $browser->script("return document.querySelector('[dusk=\"catatan-keputusan\"]').value.length;")[0]);

            $this->isiTeks($browser, '@catatan-keputusan', str_repeat('b', 30));
            $browser->assertSeeIn('@hitung-catatan', '30/2000');

            $browser->script("window.addEventListener('submit', e => e.preventDefault(), true);");
            $browser->press('@simpan-keputusan-tinjau')
                ->waitUntil("document.querySelector('[dusk=\"simpan-keputusan-tinjau\"]').disabled === true")
                ->assertSeeIn('@simpan-keputusan-tinjau', 'Menyimpan…');
            $this->assertCount(0, $this->revisiDb((int) $v1->id));
        });
    }
}
