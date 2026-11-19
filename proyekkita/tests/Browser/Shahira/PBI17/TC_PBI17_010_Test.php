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
 * TC-PBI17-010 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-010')]
class TC_PBI17_010_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI17-010 - Lamaran dengan riwayat minimal tetap tampil; keterangan lamaran Dibatalkan dan Diterima benar.
     *
     * Kendala UI: lamaran tanpa riwayat dan lamaran Dibatalkan tanpa riwayat tidak dapat dibuat lewat aplikasi,
     * karena setiap lamaran (PBI-16) dan pembatalan (PBI-18) selalu mencatat riwayat. Kedua kondisi itu tetap diuji
     * Feature test LamaranSayaTest. Tanggal pembatalan pada Excel ("3 Nov 2026") adalah contoh; di sini tanggal
     * pembatalan adalah hari test dijalankan.
     */
    public function test_TC_PBI17_010_riwayat_minimal_dan_keterangan_dibatalkan_diterima(): void
    {
        $tc = '010';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17010');
            $kode = $this->kodeUnik();
            $satuEntri = "Dusk PBI17-{$tc} Satu Entri {$kode}";
            $batal = "Dusk PBI17-{$tc} Batal {$kode}";
            $diterima = "Dusk PBI17-{$tc} Diterima {$kode}";
            $daftarJudul = [$satuEntri, $batal, $diterima];

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
            $this->kandidatMelamarLewatUi($browser, $kandidat, $daftarJudul);
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [[$proyek[$diterima], 'accept', null]]);
            $this->kandidatMembatalkanLewatUi($browser, $kandidat, [$batal]);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Periksa setiap kartu lamaran: semua tampil tanpa galat.
            $this->assertEqualsCanonicalizing($daftarJudul, $this->urutanJudulKartu($browser));
            $browser->assertDontSee('Server Error');

            $browser->assertSeeIn('#lamaran-'.$this->idLamaranDariKartu($browser, $satuEntri), 'RIWAYAT STATUS (1)')
                ->assertSeeIn('#lamaran-'.$this->idLamaranDariKartu($browser, $batal), 'Dibatalkan oleh Anda pada '.$this->tanggalIndonesia().'.')
                ->assertSeeIn('#lamaran-'.$this->idLamaranDariKartu($browser, $diterima), 'Lamaran yang sudah diterima atau ditolak tidak dapat dibatalkan.');
        });
    }
}
