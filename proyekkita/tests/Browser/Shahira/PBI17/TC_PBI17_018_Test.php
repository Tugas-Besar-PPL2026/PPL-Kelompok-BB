<?php

namespace Tests\Browser\PBI17;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Browser\Concerns\PersiapanLamaranE2E;
use Tests\DuskTestCase;

/**
 * TC-PBI17-018 (sheet PBI-17, Test_Case_ProyekKita_PBI16_PBI17_PBI18_Revisi.xlsx).
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
#[Group('TC-PBI17-018')]
class TC_PBI17_018_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use PersiapanLamaranE2E;

    /**
     * TC-PBI17-018 - Tamu dialihkan ke login saat membuka Lamaran Saya.
     *
     * Aktor uji adalah tamu: tidak ada login dan tidak membutuhkan data. Cookie dihapus di awal test (lihat browse()).
     */
    public function test_TC_PBI17_018_tamu_dialihkan_ke_login(): void
    {
        $this->browse(function (Browser $browser) {
            // Pastikan belum login, lalu buka URL /kandidat/lamaran.
            $browser->visit('/kandidat/lamaran')
                ->waitForLocation('/login')
                ->assertPathIs('/login')
                ->assertPresent('@masuk');

            // Pengguna tetap sebagai tamu: halaman login tetap dapat dibuka (middleware guest).
            $browser->visit('/login')->assertPathIs('/login')->assertPresent('@masuk');
        });
    }
}
