<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-006 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-006')]
class TC_PBI16_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
     

    /**
     * TC-PBI16-006 - Spasi di awal/akhir pesan dibuang sebelum validasi dan penyimpanan.
     */
    public function test_TC_PBI16_006_spasi_tepi_dibuang_sebelum_validasi(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16006');
            $kode = $this->kodeUnik();
            $judulA = "Dusk PBI16-006 A {$kode}";
            $judulB = "Dusk PBI16-006 B {$kode}";

            // Persiapan 1: login UMKM dan buat dua proyek lewat UI.
            $this->masukSebagaiUmkm($browser);
            $idA = $this->buatProyek($browser, $judulA);
            $idB = $this->buatProyek($browser, $judulB);

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, setujui kedua proyek, pastikan Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $idA, 'approved');
            $this->moderasiProyek($browser, $idB, 'approved');
            $this->adminMemastikanProyekDipublikasikan($browser, $idA, $judulA);
            $this->adminMemastikanProyekDipublikasikan($browser, $idB, $judulB);

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $this->bukaDetailDariEksplor($browser, $judulA);

            // Pesan A: 49 'a' + 5 spasi.
            $browser->type('@pesan-lamaran', str_repeat('a', 49).str_repeat(' ', 5))
                ->press('@kirim-lamaran')
                ->waitFor('@error-pesan')
                ->assertSeeIn('@error-pesan', 'Pesan lamaran minimal 50 karakter.');

            // Pesan B: 5 spasi + 49 'a'.
            $browser->type('@pesan-lamaran', str_repeat(' ', 5).str_repeat('a', 49))
                ->press('@kirim-lamaran')
                ->waitFor('@error-pesan')
                ->assertSeeIn('@error-pesan', 'Pesan lamaran minimal 50 karakter.');
            $this->assertNull($this->lamaranDb($kandidat['email'], $idA));

            // Pesan C pada proyek B: 3 spasi + 50 'a' + 3 spasi.
            $this->bukaDetailDariEksplor($browser, $judulB)
                ->type('@pesan-lamaran', '   '.str_repeat('a', 50).'   ')
                ->press('@kirim-lamaran')
                ->waitForText(self::MSG_LAMARAN_TERKIRIM);

            $this->assertSame(str_repeat('a', 50), $this->lamaranDb($kandidat['email'], $idB)?->message);
        });
    }
}
