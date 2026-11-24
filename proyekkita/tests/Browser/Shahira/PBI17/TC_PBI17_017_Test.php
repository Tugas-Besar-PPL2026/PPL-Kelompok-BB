<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\DataDefaultPbi16E2E;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;
use Tests\Concerns\UsesDuskTestDatabase;

/**
 * TC-PBI17-017 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
 *
 * Alur persiapan E2E (Admin, UMKM terverifikasi, Kandidat default, dan master data E2E disiapkan sekali
 * lewat seeder existing AdminSeeder; skenario lewat Chrome, tanpa loginAs/actingAs/factory/SQL tulis):
 *   1-3. Login UMKM, buat proyek lewat UI, pastikan tersimpan.  4. Logout UMKM.
 *   5-7. Login Admin, setujui di Moderasi Proyek, pastikan Dipublikasikan.  8. Logout Admin.
 *   Precondition lamaran (melamar, keputusan UMKM, pembatalan) juga lewat UI.
 *   9. Login aktor penguji lewat /login, lalu langkah sesuai Excel.
 * Database MySQL test persistent hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-lamaran')]
#[Group('pbi17')]
#[Group('TC-PBI17-017')]
class TC_PBI17_017_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use DataDefaultPbi16E2E;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI17-017 - Pengguna UMKM tidak dapat mengakses Lamaran Saya.
     *
     * Tidak membutuhkan proyek. Aktor uji adalah UMKM (akun UMKM bersama yang dibuat lewat UI).
     * Catatan: BR-17-01 menyebut pesan 'Halaman ini hanya untuk kandidat.', tetapi middleware role:kandidat berjalan
     * lebih dulu sehingga pesan yang tampil 'Anda tidak memiliki akses ke halaman ini.'. Expected Excel hanya
     * meminta respons 403 dan data lamaran tidak tampil.
     */
    public function test_TC_PBI17_017_umkm_tidak_dapat_mengakses_lamaran_saya(): void
    {
        $this->browse(function (Browser $browser) {
            // Prasyarat: UMKM terverifikasi dari AdminSeeder (read-only).
            $this->pastikanDataDefaultE2E();

            // Login aktor penguji (UMKM) lewat /login, sistem mengarahkan ke Profil Usaha.
            $this->masukSebagaiUmkm($browser);

            // Buka URL /kandidat/lamaran.
            $browser->visit('/kandidat/lamaran');
            $this->assertHalamanGalat($browser, 403, 'Anda tidak memiliki akses ke halaman ini.');
            $browser->assertDontSee('Pantau status setiap lamaran')
                ->assertMissing('nav.la-tabs')
                ->assertMissing('article.la-card');
        });
    }
}
