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
 * TC-PBI17-013 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-013')]
class TC_PBI17_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI17-013 - Nilai filter status tidak dikenal diperlakukan sebagai Semua.
     */
    public function test_TC_PBI17_013_filter_tidak_dikenal_sebagai_semua(): void
    {
        $tc = '013';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC17013');
            $kode = $this->kodeUnik();
            $menunggu = "Dusk PBI17-{$tc} Menunggu {$kode}";
            $terpilih = "Dusk PBI17-{$tc} Terpilih {$kode}";
            $daftarJudul = [$menunggu, $terpilih];

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
            $this->umkmMemutuskanLewatUi($browser, $kandidat['nama'], [[$proyek[$terpilih], 'shortlist', null]]);

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel: buka URL dengan nilai filter tidak dikenal, amati daftar dan tab aktif.
            foreach (['/kandidat/lamaran?status=bukan-status', '/kandidat/lamaran?status[]=pending'] as $url) {
            $browser->visit($url);

            $browser->waitFor('nav.la-tabs')
                ->assertSee('Pantau status setiap lamaran')
                ->assertDontSee('Server Error');

            $this->assertEqualsCanonicalizing($daftarJudul, $this->urutanJudulKartu($browser), "URL {$url} harus tampil seperti Semua.");
            $this->assertTrue($this->isiTabLamaran($browser)['Semua']['aktif'], "Tab Semua harus aktif untuk {$url}.");
        }
        });
    }
}
