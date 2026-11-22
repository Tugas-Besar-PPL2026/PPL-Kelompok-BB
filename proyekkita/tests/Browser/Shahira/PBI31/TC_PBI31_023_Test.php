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
 * TC-PBI31-023 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Database gagal menyimpan setelah berkas tertulis.
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
#[Group('TC-PBI31-023')]
class TC_PBI31_023_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-023 - Database gagal menyimpan setelah berkas tertulis.
     */
    public function test_TC_PBI31_023_kegagalan_database_membersihkan_berkas(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-023');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC023');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-023');
            $id = $k['Utama']['kesepakatan'];
            $tautan = 'https://figma.com/file/abc';

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);
            $this->isiFormPenyerahan($browser, $this->berkasUji('katalog.pdf', 'pdf'), $tautan, 'Hasil akhir.');

            $pemicu = $this->pasangPemicuGagalSimpan($id);
            try {
                $this->kirimPenyerahan($browser)
                    ->assertSeeIn('.al.bad', self::MSG_GAGAL_SIMPAN)
                    ->assertInputValue('@tautan-hasil', $tautan)
                    ->assertInputValue('@deskripsi-hasil', 'Hasil akhir.')
                    ->assertVisible('@form-penyerahan');
            } finally {
                $this->lepasPemicuGagalSimpan($pemicu);
            }

            $this->assertCount(0, $this->penyerahanDb($id));
            $this->assertSame([], $this->berkasPenyerahanDisk($id), 'Berkas yang sempat ditulis harus dibersihkan.');
        });
    }
}
