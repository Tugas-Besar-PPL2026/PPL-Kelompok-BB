<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-005 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-005')]
class TC_PBI44_005_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI44-005 - Login gagal karena password salah.
     */
    public function test_TC_PBI44_005_login_gagal_password_salah(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat terdaftar lewat halaman registrasi, lalu logout.
            $email = "rina.auth.{$this->kodeUnik()}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $email, 'Rahasia123');
            $this->keluar($browser);

            // Langkah 1-4: buka /login, isi Email terdaftar dan Kata sandi salah, klik 'Masuk'.
            $this->isiFormLogin($browser, $email, 'SalahSandi99');

            // Langkah 5: tetap di /login dengan pesan kredensial salah; Email tetap terisi, Kata sandi kosong.
            $browser->waitFor('@galat-login')
                ->assertPathIs('/login')
                ->assertSeeIn('@galat-login', 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.')
                ->assertInputValue('email', $email)
                ->assertInputValue('password', '');

            // Langkah 6: buka /kandidat/profil -> dialihkan kembali ke /login (tidak login).
            $browser->visit('/kandidat/profil')
                ->waitForLocation('/login')
                ->assertPathIs('/login')
                ->assertPresent('@masuk');
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
