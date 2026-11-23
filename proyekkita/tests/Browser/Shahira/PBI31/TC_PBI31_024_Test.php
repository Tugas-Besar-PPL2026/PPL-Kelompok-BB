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
 * TC-PBI31-024 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Konten HTML/script di deskripsi dan nama berkas.
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
#[Group('TC-PBI31-024')]
class TC_PBI31_024_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-024 - Konten HTML/script di deskripsi dan nama berkas.
     */
    public function test_TC_PBI31_024_html_script_ditampilkan_sebagai_teks(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-024');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC024');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-024');
            $skrip = '<script>alert(1)</script>';

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('katalog_script.pdf', 'pdf'), null, $skrip);
            $this->gantiNamaBerkasTerpilih($browser, 'katalog<script>.pdf');

            $this->kirimPenyerahan($browser)->assertSee(self::MSG_SUKSES_V1);
            $this->assertTidakAdaDialogAlert($browser);
            $browser->assertSeeIn('@deskripsi-versi-1', $skrip)
                ->assertSeeIn('@berkas-versi-1', 'katalogscript.pdf');
            $html = (string) $browser->script("return document.querySelector('[dusk=\"deskripsi-versi-1\"]').innerHTML;")[0];
            $this->assertStringContainsString('&lt;script&gt;', $html);

            $this->assertStringEndsWith('/katalogscript.pdf', (string) $this->penyerahanDb($k['Utama']['kesepakatan'])[0]->file_path);
        });
    }
}
