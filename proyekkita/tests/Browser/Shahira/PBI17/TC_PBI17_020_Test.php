<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI17-020 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/actingAs/factory/seeder/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi17')]
#[Group('TC-PBI17-020')]
class TC_PBI17_020_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI17-020 - Lamaran pada proyek yang sudah ditutup tetap tampil.
     *
     * Kendala UI: aplikasi belum memiliki fitur menutup proyek (tidak ada route/aksi tutup proyek di routes/web.php).
     * Proyek tetap dibuat UMKM dan disetujui Admin lewat UI, lalu SATU kolom status proyek diubah menjadi
     * closed di database testing. Dilewati secara default; aktifkan dengan DUSK_IZINKAN_SIMULASI_DB=true bila
     * diizinkan dosen.
     */
    #[Group('butuh-simulasi-db')]
    public function test_TC_PBI17_020_lamaran_pada_proyek_ditutup_tetap_tampil(): void
    {
        $this->butuhSimulasiDb('TC-PBI17-020', 'membutuhkan proyek berstatus Ditutup; aplikasi belum memiliki fitur menutup proyek di UI.');

        $tc = '020';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17020');
            $kode = $this->kodeUnik();
            $judul = "Dusk PBI17-{$tc} Ditutup {$kode}";
            $daftarJudul = [$judul];

            // Persiapan 1-3: login UMKM, buat proyek lewat UI, pastikan tersimpan (Proyek Saya: Menunggu Moderasi).
            $this->masukSebagaiUmkm($browser);
            $proyek = [];
            foreach ($daftarJudul as $item) {
                $proyek[$item] = $this->buatProyek($browser, $item);
                $this->umkmMemastikanProyekTersimpan($browser, $proyek[$item], $item);
            }

            // Persiapan 4: logout UMKM.
            $this->keluar($browser);

            // Persiapan 5-7: login Admin, setujui di Moderasi Proyek, pastikan proyek Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            foreach ($proyek as $item => $id) {
                $this->moderasiProyek($browser, $id, 'approved');
                $this->adminMemastikanProyekDipublikasikan($browser, $id, $item);
            }

            // Persiapan 8: logout Admin.
            $this->keluar($browser);

            // Precondition lamaran lewat UI.
            $this->kandidatMelamarLewatUi($browser, $kandidat, [$judul]);

            // Kondisi "proyek Ditutup" (belum tersedia di UI).
            $this->simulasikanKondisiProyek($proyek[$judul], ['status' => 'closed']);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Cari kartu lamaran pada proyek tersebut.
            $kartu = '#lamaran-'.$this->idLamaranDariKartu($browser, $judul);
            $browser->assertSeeIn("{$kartu} h3", $judul)
                ->assertSeeIn($kartu, self::NAMA_USAHA_E2E)
                ->assertSeeIn("{$kartu} .la-meta .bd", 'Menunggu Keputusan');
        });
    }
}
