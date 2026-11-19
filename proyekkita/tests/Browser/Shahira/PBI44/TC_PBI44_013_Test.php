<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-013 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-013')]
class TC_PBI44_013_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI44-013 - Tamu yang membuka halaman terproteksi diarahkan ke halaman Masuk, lalu ke halaman awal peran setelah login.
     */
    public function test_TC_PBI44_013_tamu_diarahkan_ke_login_lalu_halaman_awal_peran(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat dibuat lewat halaman registrasi lalu logout.
            $email = "rina.tamu.{$this->kodeUnik()}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $email, 'Rahasia123');
            $this->keluar($browser);

            // Langkah 1-2: tamu membuka halaman terproteksi -> dialihkan ke /login.
            $browser->visit('/kandidat/lamaran')
                ->waitForLocation('/login')
                ->assertPathIs('/login')
                ->waitFor('@masuk');

            // Langkah 3: isi form pada halaman /login tersebut, klik 'Masuk'.
            $browser->type('email', $email)
                ->type('password', 'Rahasia123')
                ->press('@masuk');

            // Langkah 4: diarahkan ke halaman awal Kandidat, bukan ke /kandidat/lamaran.
            $browser->waitForLocation('/kandidat/profil')
                ->assertPathIs('/kandidat/profil')
                ->assertPathIsNot('/kandidat/lamaran')
                ->assertSeeIn('h1', 'Profil & Portofolio');
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
