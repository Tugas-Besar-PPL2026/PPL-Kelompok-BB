<?php

namespace Tests\Browser\PBI44;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI44-003 (sheet PBI-44, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
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
    use UsesDuskTestDatabase;

    /**
     * TC-PBI44-003 - Login gagal karena input wajib kosong.
     */
    public function test_TC_PBI44_003_login_gagal_input_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat terdaftar lewat halaman registrasi, lalu logout.
            $email = "rina.auth.{$this->kodeUnik()}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $email, 'Rahasia123');
            $this->keluar($browser);

            $variasi = [
                'a' => ['', '', ['@galat-email' => 'Email wajib diisi.', '@galat-password' => 'Kata sandi wajib diisi.']],
                'b' => ['', 'Rahasia123', ['@galat-email' => 'Email wajib diisi.']],
                'c' => [$email, '', ['@galat-password' => 'Kata sandi wajib diisi.']],
            ];

            foreach ($variasi as $kodeVariasi => [$isiEmail, $isiSandi, $pesan]) {
                // Langkah 1-2: buka /login, isi form sesuai variasi, klik 'Masuk'.
                $this->isiFormLogin($browser, $isiEmail, $isiSandi)->waitFor(array_key_first($pesan));

                // Ditolak: tetap di /login dengan pesan validasi sesuai, tanpa pesan kredensial salah.
                $browser->assertPathIs('/login')->assertMissing('@galat-login');
                foreach ($pesan as $selector => $teks) {
                    $browser->assertSeeIn($selector, $teks);
                }
                if (! array_key_exists('@galat-email', $pesan)) {
                    $browser->assertMissing('@galat-email');
                }
                if (! array_key_exists('@galat-password', $pesan)) {
                    $browser->assertMissing('@galat-password');
                }
            }

            // Langkah 4: buka /kandidat/profil -> dialihkan ke /login (tidak login).
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
