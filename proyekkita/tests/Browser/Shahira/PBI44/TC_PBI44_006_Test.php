<?php

namespace Tests\Browser\PBI44;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-006 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-006')]
class TC_PBI44_006_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI44-006 - Login gagal karena email belum terdaftar, dengan pesan yang sama seperti kata sandi salah.
     */
    public function test_TC_PBI44_006_login_gagal_email_tidak_terdaftar(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: email uji belum terdaftar (dibaca dari database, read-only).
            $email = "belum.terdaftar.{$this->kodeUnik()}@proyekkita.test";
            $this->assertSame(0, DB::table('users')->where('email', $email)->count());

            // Langkah 1-3: buka /login, isi Email yang belum terdaftar, klik 'Masuk'.
            $this->isiFormLogin($browser, $email, 'Rahasia123');

            // Langkah 4: pesan sama persis dengan kata sandi salah; Email tetap terisi; tidak login.
            $browser->waitFor('@galat-login')
                ->assertPathIs('/login')
                ->assertSeeIn('@galat-login', 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.')
                ->assertInputValue('email', $email);
            $this->assertBelumLogin($browser);
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

    /** Verifikasi pengguna TIDAK login: halaman terproteksi mengalihkan tamu ke /login. */
    private function assertBelumLogin(Browser $browser): void
    {
        $browser->visit('/kandidat/profil')
            ->waitForLocation('/login')
            ->assertPathIs('/login')
            ->assertPresent('@masuk');
    }
}
