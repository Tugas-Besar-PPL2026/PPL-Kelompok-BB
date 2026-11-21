<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-007 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-007')]
class TC_PBI44_007_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI44-007 - Login gagal karena Email dan Kata sandi sama-sama kosong.
     */
    public function test_TC_PBI44_007_login_gagal_email_dan_kata_sandi_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            // Langkah 1-3: buka /login, isi form sesuai input, klik 'Masuk'.
            $this->isiFormLogin($browser, '', '')->waitFor('@galat-email');

            // Langkah 4: ditolak oleh validasi, tanpa pesan kredensial salah.
            $browser->assertPathIs('/login')
                ->assertMissing('@galat-login')
                ->assertSeeIn('@galat-email', 'Email wajib diisi.')
                ->assertSeeIn('@galat-password', 'Kata sandi wajib diisi.');
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
