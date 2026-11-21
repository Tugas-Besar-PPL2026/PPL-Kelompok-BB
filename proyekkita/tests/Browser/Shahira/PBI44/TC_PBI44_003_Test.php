<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-003 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run. Akun Admin memakai akun
 * yang sudah disiapkan satu kali di database test (DUSK_ADMIN_EMAIL / DUSK_ADMIN_PASSWORD).
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi44')]
#[Group('TC-PBI44-003')]
class TC_PBI44_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI44-003 - Login berhasil sebagai Admin dan diarahkan ke halaman awal Admin.
     */
    public function test_TC_PBI44_003_login_admin_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Admin sudah disiapkan di database test (Admin tidak dapat mendaftar lewat aplikasi).
            // Langkah 1-3: buka /login, isi Email dan Kata sandi Admin, klik 'Masuk'.
            $this->isiFormLogin($browser, $this->emailAdmin(), $this->sandiAdmin());

            // Langkah 4: halaman tujuan dan menu samping sesuai peran.
            $browser->waitForLocation('/admin/data-master')
                ->assertPathIs('/admin/data-master')
                ->assertSeeIn('h1', 'Data Master')
                ->assertSeeIn('aside.side', 'Keluar')
                ->assertSeeIn('aside.side', 'Verifikasi UMKM')
                ->assertSeeIn('aside.side', 'Moderasi Proyek')
                ->assertDontSeeIn('aside.side', 'Lamaran Saya')
                ->assertDontSeeIn('aside.side', 'Seleksi Kandidat');

            // Langkah 5: /login khusus tamu, pengguna yang sudah login dialihkan ke halaman awalnya.
            $browser->visit('/login')->waitForLocation('/admin/data-master')->assertPathIs('/admin/data-master');
        });
    }

    /** Langkah login lewat form /login sesuai Steps Excel: buka /login, isi Email dan Kata sandi, klik 'Masuk'. */
    private function isiFormLogin(Browser $browser, string $email, string $sandi): Browser
    {
        $browser->visit('/login')->waitFor('@masuk');
        $email === '' ? $browser->clear('email') : $browser->type('email', $email);
        $sandi === '' ? $browser->clear('password') : $browser->type('password', $sandi);

        return $browser->press('@masuk');
    }
}
