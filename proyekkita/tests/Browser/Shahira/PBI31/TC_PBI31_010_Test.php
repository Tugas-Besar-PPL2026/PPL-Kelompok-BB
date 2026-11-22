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
 * TC-PBI31-010 (sheet PBI-31, Test_Case_Kelompok_BB_SI4801_2.xlsx) - Format tidak didukung: DOCX (A) dan .pdf palsu (B).
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
#[Group('TC-PBI31-010')]
class TC_PBI31_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use BerkasUjiPenyerahanE2E;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use PersiapanPenyerahanE2E;

    /**
     * TC-PBI31-010 - Format tidak didukung: DOCX (A) dan .pdf palsu (B).
     */
    public function test_TC_PBI31_010_format_docx_dan_pdf_palsu_ditolak(): void
    {
        $this->butuhSimulasiKesepakatan('TC-PBI31-010');

        $this->browse(function (Browser $browser) {
            $kandidat = $this->siapkanKandidatTambahan($browser, 'PBI31 TC010');
            $k = $this->siapkanKesepakatanLewatUi($browser, $kandidat, ['Utama' => []], 'TC31-010');
            $id = $k['Utama']['kesepakatan'];

            $this->masukSebagaiKandidat($browser, $kandidat);
            $this->bukaPenyerahanHasil($browser, $k['Utama']['judul']);

            // A: DOCX
            $this->isiFormPenyerahan($browser, $this->berkasUji('laporan.docx', 'docx', self::MB), null, 'Hasil akhir.')
                ->assertSeeIn('@status-berkas-terpilih', 'Format tidak didukung');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_FORMAT);

            // B: berekstensi .pdf tetapi isinya teks
            $this->isiFormPenyerahan($browser, $this->berkasUji('katalog.pdf', 'teks'), null, 'Hasil akhir.');
            $this->kirimPenyerahan($browser)->assertSeeIn('@error-berkas', self::MSG_FORMAT);

            $this->assertCount(0, $this->penyerahanDb($id));
            $this->assertSame([], $this->berkasPenyerahanDisk($id), 'Tidak boleh ada berkas tersimpan.');
        });
    }
}
