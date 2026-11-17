<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI17-012 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-012')]
class TC_PBI17_012_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;

    /**
     * TC-PBI17-012 - Filter status menampilkan hanya lamaran berstatus tersebut.
     */
    public function test_TC_PBI17_012_filter_status(): void
    {
        $tc = '012';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17012');
            $kode = $this->kodeUnik();
            $menunggu = "Dusk PBI17-{$tc} Menunggu {$kode}";
            $terpilih = "Dusk PBI17-{$tc} Terpilih {$kode}";
            $diterima = "Dusk PBI17-{$tc} Diterima {$kode}";
            $daftarJudul = [$menunggu, $terpilih, $diterima];

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
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [
                [$proyek[$terpilih], 'shortlist', null],
                [$proyek[$diterima], 'accept', null],
            ]);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: klik menu Lamaran Saya di menu samping.
            $this->bukaLamaranSaya($browser);
            // Klik tab 'Terpilih Sementara', amati daftar dan tab aktif.
            $this->klikTautan($browser, 'Terpilih Sementara')
                ->assertPathIs('/kandidat/lamaran')
                ->assertQueryStringHas('status', 'shortlisted');
            $this->assertSame([$terpilih], $this->urutanJudulKartu($browser));
            $this->assertTrue($this->isiTabLamaran($browser)['Terpilih Sementara']['aktif']);

            // Klik tab 'Semua'.
            $this->klikTautan($browser, 'Semua')->assertQueryStringMissing('status');
            $this->assertEqualsCanonicalizing($daftarJudul, $this->urutanJudulKartu($browser));
            $this->assertTrue($this->isiTabLamaran($browser)['Semua']['aktif']);
        });
    }
}
