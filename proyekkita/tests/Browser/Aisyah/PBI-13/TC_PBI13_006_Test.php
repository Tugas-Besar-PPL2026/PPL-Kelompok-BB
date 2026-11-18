<?php

namespace Tests\Browser\Aisyah\PBI13;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EPbi131415;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI13-006 (sheet PBI 13, PBI 13 14 15.xlsx).
 *
 * Alur E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   Persiapan: UMKM baru terverifikasi, proyek dibuat lewat form dan disetujui Admin (Dipublikasikan);
 *              dua kandidat terdaftar dengan data diri lengkap dan keterampilan Desain Kemasan.
 *   Langkah  : Kandidat A melamar; Kandidat B melamar lalu membatalkan; UMKM membuka Proyek Saya.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-proyek')]
#[Group('pbi13')]
#[Group('TC-PBI13-006')]
class TC_PBI13_006_Test extends DuskTestCase
{
    use AlurE2EPbi131415;
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI13-006 - Jumlah pelamar tidak menghitung lamaran yang dibatalkan kandidat.
     */
    public function test_TC_PBI13_006_jumlah_pelamar_tanpa_lamaran_dibatalkan(): void
    {
        $this->browse(function (Browser $browser) {
            // Persiapan: UMKM terverifikasi dengan satu proyek Dipublikasikan, dua kandidat lengkap.
            $akun = $this->siapkanUmkmBaru($browser, 'PBI13', $this->profilUniqlo());
            $judul = "Redesain Logo Baju {$akun['kode']}";
            $proyekId = $this->terbitkanProyekUmkm($browser, $akun, $this->dataProyek(['judul' => $judul]));
            $kandidatA = $this->siapkanKandidatExcel($browser, 'Kandidat A');
            $kandidatB = $this->siapkanKandidatExcel($browser, 'Kandidat B');

            // Langkah 1-2: Kandidat A login, Eksplor Proyek, Lihat Detail & Lamar, Kirim Lamaran.
            $this->masukSebagaiKandidat($browser, $kandidatA);
            $this->ajukanLamaran($browser, $judul)->assertSee(self::MSG_LAMARAN_TERKIRIM);
            $this->keluar($browser);

            // Langkah 3: Kandidat B login dan melamar proyek yang sama.
            $this->masukSebagaiKandidat($browser, $kandidatB);
            $this->ajukanLamaran($browser, $judul)->assertSee(self::MSG_LAMARAN_TERKIRIM);

            // Langkah 4: Kandidat B membatalkan lamaran lewat Lamaran Saya.
            $this->bukaLamaranSaya($browser);
            $this->batalkanLewatDialog($browser, $this->idLamaranDariKartu($browser, $judul))
                ->assertSee(self::MSG_BATAL_SUKSES);
            $this->keluar($browser);

            // Langkah 5: UMKM pemilik proyek login, menu Proyek Saya.
            $this->masukSebagaiUmkm($browser, $akun);
            $this->klikTautan($browser, 'Proyek Saya')
                ->assertPathIs('/umkm/proyek')
                ->assertSeeIn("@jumlah-pelamar-{$proyekId}", '1');

            $this->assertSame('1', trim($browser->text("@jumlah-pelamar-{$proyekId}")), 'Jumlah pelamar pada kartu proyek adalah 1.');
            $this->assertSame(1, $this->kartuRingkasan($browser)['pelamar'], 'Kartu ringkasan Total Pelamar bernilai 1.');

            // Verifikasi tambahan di database (hanya membaca).
            $status = DB::table('applications')->where('project_id', $proyekId)->orderBy('id')->pluck('status')->all();
            $this->assertSame(['pending', 'cancelled'], $status, 'Dua lamaran tersimpan, satu dibatalkan.');
            $this->assertSame('pending', $this->lamaranDb($kandidatA['email'], $proyekId)->status);
            $this->assertSame('cancelled', $this->lamaranDb($kandidatB['email'], $proyekId)->status);
        });
    }
}
