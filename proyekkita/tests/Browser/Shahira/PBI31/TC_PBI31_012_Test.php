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
 * TC-PBI31-012 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Berkas 0 byte (A) dan unggahan rusak/terputus (B).
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
#[Group('TC-PBI31-012')]
class TC_PBI31_012_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-012 - Berkas 0 byte (A) dan unggahan rusak/terputus (B).
     */
    public function test_TC_PBI31_012_berkas_kosong_dan_unggahan_gagal_ditolak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-012');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC012');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-012');

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            // A: 0 byte
            $this->isiFormPenyerahan($browser, $this->berkasUji('kosong.pdf', 'kosong'), null, 'Hasil akhir.')
                ->assertSeeIn('@status-berkas-terpilih', 'Berkas kosong');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_KOSONG);

            // B: unggahan gagal di sisi PHP. UPLOAD_ERR_PARTIAL tidak dapat dibuat dari browser; dipakai berkas 31 MB
            // yang melebihi upload_max_filesize server uji (30M) sehingga PHP menandainya gagal (UploadedFile::isValid()
            // = false), jalur kode yang sama dengan unggahan terputus.
            $this->isiFormPenyerahan($browser, $this->berkasUji('katalog.pdf', 'pdf', 31 * self::MB), null, 'Hasil akhir.');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_GAGAL_UNGGAH);

            $this->assertCount(0, $this->penyerahanDb($k['Utama']['kesepakatan']));
        });
    }
}
