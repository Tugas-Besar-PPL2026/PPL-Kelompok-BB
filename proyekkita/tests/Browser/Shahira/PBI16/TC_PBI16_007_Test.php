<?php

namespace Tests\Browser\PBI16;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI16-007 (sheet PBI-16, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (semua lewat Chrome, tanpa loginAs/factory/seeder/SQL):
 *   1. Login UMKM, buat proyek lewat UI.  2. Logout UMKM.
 *   3. Login Admin, setujui proyek di Moderasi Proyek.  4. Logout Admin.
 *   5. Login aktor uji lewat /login, lalu jalankan langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi16')]
#[Group('TC-PBI16-007')]
class TC_PBI16_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI16-007 - Pesan lamaran berbentuk array (request dimanipulasi) ditolak.
     *
     * Langkah "(Uji HTTP)" dilakukan dari Chrome: kolom pesan diubah menjadi message[] lewat DOM (seperti DevTools)
     * lalu tombol Kirim Lamaran diklik, sehingga request memakai sesi login dan token CSRF browser.
     * Catatan implementasi: setelah validasi gagal, old('message') berisi array dan _lamar.blade.php menampilkannya
     * dengan {{ }}, sehingga halaman kemungkinan error 500. Bila test gagal di sini, itu temuan bug aplikasi.
     */
    public function test_TC_PBI16_007_pesan_berbentuk_array_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat akun lewat UI (dipakai ulang bila sudah ada): master data, UMKM terverifikasi, akun kandidat.
            $this->pastikanUmkmTerverifikasi($browser);
            $kandidat = $this->siapkanKandidat($browser, 'TC16007');
            $judul = "Dusk PBI16-007 {$this->kodeUnik()}";

            // Persiapan 1: login UMKM dan buat proyek lewat UI (Buat Proyek -> Tinjau & Ajukan -> ajukan ke moderasi).
            $this->masukSebagaiUmkm($browser);
            $proyekId = $this->buatProyek($browser, $judul);

            // Persiapan 2: logout UMKM.
            $this->keluar($browser);

            // Persiapan 3: login Admin, setujui di Moderasi Proyek, pastikan proyek Dipublikasikan.
            $this->masukSebagaiAdmin($browser);
            $this->moderasiProyek($browser, $proyekId, 'approved');
            $this->adminMemastikanProyekDipublikasikan($browser, $proyekId, $judul);

            // Persiapan 4: logout Admin.
            $this->keluar($browser);

            // Persiapan 5: login Kandidat lewat halaman /login.
            $this->masukSebagaiKandidat($browser, $kandidat);

            // Langkah uji sesuai Excel.
            $this->bukaDetailDariEksplor($browser, $judul);

            // Input: message[]=a, message[]=b
            $browser->script(<<<'JS'
                const area = document.getElementById('message');
                area.name = 'message[]';
                area.value = 'a';
                const kedua = document.createElement('input');
                kedua.type = 'hidden';
                kedua.name = 'message[]';
                kedua.value = 'b';
                document.getElementById('form-lamaran').appendChild(kedua);
            JS);

            $browser->press('@kirim-lamaran')
                ->waitFor('@error-pesan')
                ->assertPathIs("/kandidat/proyek/{$proyekId}")
                ->assertDontSee(self::MSG_LAMARAN_TERKIRIM);

            $this->assertNull($this->lamaranDb($kandidat['email'], $proyekId));
        });
    }
}
