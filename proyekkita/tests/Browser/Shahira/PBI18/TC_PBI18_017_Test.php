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
 * TC-PBI18-017 (sheet PBI-18, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI18-017')]
class TC_PBI18_017_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
    use PersiapanLamaranE2E;
     

    /**
     * TC-PBI18-017 - Nilai filter tidak valid pada permintaan pembatalan diabaikan.
     *
     * Kedua nilai input (status='<script>' dan status[]='x') masing-masing diuji pada lamaran sendiri,
     * karena satu lamaran hanya dapat dibatalkan sekali.
     */
    public function test_TC_PBI18_017_nilai_filter_tidak_valid_diabaikan(): void
    {
        $tc = '017';

        $this->browse(function (Browser $browser) use ($tc) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC18017');
            $kode = $this->kodeUnik();
            $judulSatu = "Dusk PBI18-{$tc} Satu {$kode}";
            $judulDua = "Dusk PBI18-{$tc} Dua {$kode}";
            $daftarJudul = [$judulSatu, $judulDua];

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

            // Persiapan 9: login aktor penguji (Kandidat) lewat /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            foreach ([[$judulSatu, 'status', '<script>'], [$judulDua, 'status[]', 'x']] as [$item, $nama, $nilai]) {
                $browser->visit('/kandidat/lamaran')->waitFor('nav.la-tabs');
                $id = $this->idLamaranDariKartu($browser, $item);

                // (Uji HTTP) kirim PATCH dengan field status bernilai tidak valid (form dialog asli + field tambahan).
                $browser->click("@batalkan-lamaran-{$id}")->waitFor("@dialog-batal-{$id}");
                $browser->driver->executeScript(
                    "const form = document.querySelector('#batal-lamaran-' + arguments[0] + ' form[data-batal-form]');"
                    ."form.querySelectorAll('input[name=\"status\"]').forEach(el => el.remove());"
                    ."const input = document.createElement('input'); input.type = 'hidden'; input.name = arguments[1]; input.value = arguments[2];"
                    .'form.appendChild(input);',
                    [$id, $nama, $nilai]
                );
                $browser->press("@konfirmasi-batal-{$id}")->waitForText(self::MSG_BATAL_SUKSES);

                // Periksa URL tujuan dan status lamaran.
                $browser->assertPathIs('/kandidat/lamaran')
                    ->assertQueryStringMissing('status')
                    ->assertDontSee('Server Error');
                $this->assertTrue($this->isiTabLamaran($browser)['Semua']['aktif'], "Filter {$nama} harus diabaikan.");
                $this->assertSame('cancelled', $this->statusLamaranDb($id));
            }
        });
    }
}
