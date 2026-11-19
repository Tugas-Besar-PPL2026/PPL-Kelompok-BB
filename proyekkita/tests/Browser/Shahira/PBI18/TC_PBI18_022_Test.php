<?php

namespace Tests\Browser\PBI18;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI18-022 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/actingAs/factory/seeder/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Langkah "(Uji HTTP)" dijalankan dari Chrome dengan sesi login aktor (form dimanipulasi seperti lewat DevTools).
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi18')]
#[Group('TC-PBI18-022')]
class TC_PBI18_022_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI18-022 - Pembatalan dapat dilakukan di layar smartphone (375x812).
     *
     * Kendala UI: pada lebar <= 1000px menu samping disembunyikan CSS dan aplikasi belum memiliki tombol menu
     * mobile, sehingga langkah "Buka menu Lamaran Saya" dilakukan lewat URL /kandidat/lamaran.
     */
    public function test_TC_PBI18_022_pembatalan_di_layar_smartphone(): void
    {
        $tc = '022';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC18022');
            $judul = "Dusk PBI18-{$tc} Lamaran {$this->kodeUnik()}";
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

            // Langkah 1: atur ukuran jendela browser menjadi 375x812.
            $browser->resize(375, 812);

            // Langkah 2-5: login Kandidat lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah 6: buka menu Lamaran Saya (lewat URL, lihat kendala UI di atas).
            $browser->visit('/kandidat/lamaran')->waitFor('nav.la-tabs')->assertSee('Lamaran Saya');
            $id = $this->idLamaranDariKartu($browser, $judul);

            // Langkah 7: klik 'Batalkan Lamaran' lalu 'Ya, Batalkan Lamaran'.
            $browser->click("@batalkan-lamaran-{$id}")
                ->waitFor("@dialog-batal-{$id}")
                ->assertSeeIn("@dialog-batal-{$id}", 'Batalkan lamaran?')
                ->assertSeeIn("@dialog-batal-{$id}", $judul)
                ->assertVisible("@tutup-batal-{$id}")
                ->assertVisible("@konfirmasi-batal-{$id}");

            // Dialog terbaca utuh: kotak dialog berada di dalam lebar layar.
            $muat = $browser->driver->executeScript(
                "const r = document.querySelector('[dusk=\"dialog-batal-' + arguments[0] + '\"] .modal, [dusk=\"dialog-batal-' + arguments[0] + '\"] > *').getBoundingClientRect();"
                .'return r.left >= 0 && r.right <= window.innerWidth;',
                [$id]
            );
            $this->assertTrue($muat, 'Kotak dialog harus muat di layar 375px.');

            $browser->press("@konfirmasi-batal-{$id}")
                ->waitForText(self::MSG_BATAL_SUKSES)
                ->assertSeeIn("#lamaran-{$id} .la-meta .bd", 'Dibatalkan');

            $this->assertSame('cancelled', $this->statusLamaranDb($id));
        });
    }
}
