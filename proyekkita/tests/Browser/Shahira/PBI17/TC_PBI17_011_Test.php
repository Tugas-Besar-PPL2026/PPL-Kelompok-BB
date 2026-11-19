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
 * TC-PBI17-011 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-011')]
class TC_PBI17_011_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI17-011 - Jumlah pada tab sesuai data.
     */
    public function test_TC_PBI17_011_jumlah_pada_tab_sesuai_data(): void
    {
        $tc = '011';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17011');
            $kode = $this->kodeUnik();
            $menunggu1 = "Dusk PBI17-{$tc} Menunggu 1 {$kode}";
            $menunggu2 = "Dusk PBI17-{$tc} Menunggu 2 {$kode}";
            $diterima = "Dusk PBI17-{$tc} Diterima {$kode}";
            $batal = "Dusk PBI17-{$tc} Batal {$kode}";
            $daftarJudul = [$menunggu1, $menunggu2, $diterima, $batal];

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
            // Bandingkan angka pada tab Semua dan setiap tab status dengan data.
            $this->assertSame([
                'Semua' => 4, 'Menunggu Keputusan' => 2, 'Terpilih Sementara' => 0,
                'Diterima' => 1, 'Ditolak' => 0, 'Dibatalkan' => 1,
            ], array_map(fn ($tab) => $tab['jumlah'], $this->isiTabLamaran($browser)));
        });
    }
}
