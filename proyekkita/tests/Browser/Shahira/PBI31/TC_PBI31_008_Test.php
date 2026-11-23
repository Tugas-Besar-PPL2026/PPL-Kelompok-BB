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
 * TC-PBI31-008 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Isian dipertahankan setelah validasi gagal (A); penghitung karakter dan tombol 'Mengirim…' (B).
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
#[Group('TC-PBI31-008')]
class TC_PBI31_008_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-008 - Isian dipertahankan setelah validasi gagal (A); penghitung karakter dan tombol 'Mengirim…' (B).
     */
    public function test_TC_PBI31_008_isian_dipertahankan_penghitung_dan_tombol_mengirim(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-008');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC008');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-008');
            $tautan = 'https://figma.com/file/abc';
            $pdf = $this->berkasUji('katalog_v1.pdf', 'pdf');

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            // A: validasi gagal -> tautan & deskripsi dipertahankan, berkas harus dipilih ulang
            $this->isiFormPenyerahan($browser, $pdf, $tautan, str_repeat('a', 2001));
            $this->kirimPenyerahan($browser)
                ->assertSeeIn('@error-deskripsi', self::MSG_DESKRIPSI_MAKS)
                ->assertInputValue('@tautan-hasil', $tautan)
                ->assertSeeIn('@hitung-deskripsi', '2001/2000')
                ->assertMissing('@berkas-terpilih')
                ->assertSeeIn('@area-unggah', 'Seret file ke sini atau pilih file');
            $this->assertSame(2001, (int) $browser->script("return document.querySelector('[dusk=\"deskripsi-hasil\"]').value.length;")[0]);
            $this->assertSame(0, (int) $browser->script("return document.querySelector('[dusk=\"input-berkas\"]').files.length;")[0]);

            // B: penghitung karakter dan status tombol saat mengirim
            $this->isiTeks($browser, '@deskripsi-hasil', str_repeat('b', 45));
            $browser->assertSeeIn('@hitung-deskripsi', '45/2000');

            // Pengiriman ditahan di browser agar keadaan tombol setelah diklik dapat diamati.
            $browser->script("window.addEventListener('submit', e => e.preventDefault(), true);");
            $browser->press('@kirim-penyerahan')
                ->waitUntil("document.querySelector('[dusk=\"kirim-penyerahan\"]').disabled === true")
                ->assertSeeIn('@kirim-penyerahan', 'Mengirim…');

            $this->assertCount(0, $this->penyerahanDb($k['Utama']['kesepakatan']));
        });
    }
}
