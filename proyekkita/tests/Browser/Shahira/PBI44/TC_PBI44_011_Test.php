<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-011 (sheet PBI-44, Test_Case_Kelompok_BB_SI4801_1.xlsx).
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
#[Group('TC-PBI44-011')]
class TC_PBI44_011_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    //use UsesDuskTestDatabase;

    /**
     * TC-PBI44-011 - Login berhasil setelah 4 kali gagal dan penghitung percobaan gagal direset.
     */
    public function test_TC_PBI44_011_login_berhasil_setelah_gagal_dan_penghitung_direset(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat dibuat lewat halaman registrasi lalu logout.
            $email = "rina.reset.{$this->kodeUnik()}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $email, 'Rahasia123');
            $this->keluar($browser);

            // Langkah 1: 4 kali login dengan kata sandi salah (masih di bawah batas 5 kali).
            for ($i = 1; $i <= 4; $i++) {
                $this->loginGagal($browser, $email, 'SalahSandi99');
            }

            // Langkah 2-3: login dengan kata sandi benar berhasil.
            $this->isiFormLogin($browser, $email, 'Rahasia123')
                ->waitForLocation('/kandidat/profil')
                ->assertPathIs('/kandidat/profil');

            // Langkah 4-6: setelah keluar, 2 kali gagal hanya menampilkan pesan kredensial salah
            // (tanpa reset, percobaan kedua sudah melewati batas dan akan diblokir).
            $this->keluar($browser);
            for ($i = 1; $i <= 2; $i++) {
                $this->loginGagal($browser, $email, 'SalahSandi99');
            }
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

    /** Satu percobaan login gagal: tetap di /login dengan pesan kredensial salah. */
    private function loginGagal(Browser $browser, string $email, string $sandi): void
    {
        $this->isiFormLogin($browser, $email, $sandi)
            ->waitFor('@galat-login')
            ->assertPathIs('/login')
            ->assertSeeIn('@galat-login', 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.')
            ->assertDontSee('Terlalu banyak percobaan masuk');
    }
}
