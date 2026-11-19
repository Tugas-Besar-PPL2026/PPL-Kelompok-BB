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
 * TC-PBI17-004 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-004')]
class TC_PBI17_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI17-004 - Kandidat tidak melihat lamaran kandidat lain.
     */
    public function test_TC_PBI17_004_kandidat_tidak_melihat_lamaran_kandidat_lain(): void
    {
        $tc = '004';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidatA = $this->siapkanKandidat($browser, 'TC17004A');
            $kandidatB = $this->siapkanKandidat($browser, 'TC17004B');
            $kode = $this->kodeUnik();
            $judulA = "Dusk PBI17-{$tc} Milik A {$kode}";
            $judulB = "Dusk PBI17-{$tc} Milik B {$kode}";
            $daftarJudul = [$judulA, $judulB];

            $catatanB = "Catatan khusus kandidat B {$kode}";

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
            $this->kandidatMelamarLewatUi($browser, $kandidatA, [$judulA]);
            $this->kandidatMelamarLewatUi($browser, $kandidatB, [$judulB]);
            $this->umkmMemutuskanLewatUi($browser, $kandidatB['nama'], [[$proyek[$judulB], 'shortlist', $catatanB]]);

            // Persiapan 9: login aktor penguji (Kandidat A) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidatA);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Periksa daftar lamaran dan angka pada setiap tab.
            $browser->assertSee($judulA)
                ->assertDontSee($judulB)
                ->assertDontSee($catatanB);
            $this->assertSame([$judulA], $this->urutanJudulKartu($browser));

            $tab = array_map(fn ($item) => $item['jumlah'], $this->isiTabLamaran($browser));
            $this->assertSame([
                'Semua' => 1, 'Menunggu Keputusan' => 1, 'Terpilih Sementara' => 0,
                'Diterima' => 0, 'Ditolak' => 0, 'Dibatalkan' => 0,
            ], $tab, 'Lamaran kandidat B tidak boleh ikut terhitung.');
        });
    }
}
