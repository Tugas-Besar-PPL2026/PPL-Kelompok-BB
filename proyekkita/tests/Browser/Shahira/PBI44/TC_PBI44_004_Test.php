<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-004 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-004')]
class TC_PBI44_004_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI44-004 - Login berhasil walaupun Email ditulis dengan huruf besar dan diawali spasi.
     */
    public function test_TC_PBI44_004_login_email_huruf_besar_dan_spasi_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat dibuat lewat halaman registrasi (email huruf kecil) lalu logout.
            $email = "rina.kapital.{$this->kodeUnik()}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kapital', $email, 'Rahasia123');
            $this->keluar($browser);

            // Langkah 1-3: login dengan Email diawali spasi dan ditulis huruf besar.
            $this->isiFormLogin($browser, '  '.mb_strtoupper($email), 'Rahasia123');

            // Langkah 4: login berhasil ke halaman awal Kandidat.
            $browser->waitForLocation('/kandidat/profil')
                ->assertPathIs('/kandidat/profil')
                ->assertSeeIn('h1', 'Profil & Portofolio')
                ->assertSee('Keluar');
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
