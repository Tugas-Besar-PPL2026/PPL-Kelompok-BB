<?php

namespace Tests\Browser\PBI45;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI45-002 (sheet PBI-45, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run. Akun Admin memakai akun
 * yang sudah disiapkan satu kali di database test (DUSK_ADMIN_EMAIL / DUSK_ADMIN_PASSWORD).
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi45')]
#[Group('TC-PBI45-002')]
class TC_PBI45_002_Test extends DuskTestCase
{


    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI45-002 - Halaman terproteksi tidak dapat diakses setelah logout.
     */
    public function test_TC_PBI45_002_halaman_terproteksi_tidak_dapat_diakses_setelah_logout(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: Kandidat login lewat /login, membuka menu Lamaran Saya, lalu klik 'Keluar'.
            $akun = ['email' => "rina.auth.{$this->kodeUnik()}@proyekkita.test", 'sandi' => 'Rahasia123'];
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $akun['email'], $akun['sandi']);
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $akun);
            $this->bukaLamaranSaya($browser);
            $browser->press('Keluar')->waitForLocation('/');

            // Langkah 1: klik tombol Back browser (kembali ke halaman Lamaran Saya sebelum logout).
            $browser->back();

            // Langkah 2: muat ulang halaman. Bila Chrome menampilkan halaman lama dari cache, refresh harus
            // meminta ulang ke server dan dialihkan ke /login; data lamaran tidak tampil lagi.
            $browser->refresh()
                ->waitForLocation('/login')
                ->assertPathIs('/login')
                ->assertPresent('@masuk')
                ->assertDontSee('Pantau status setiap lamaran');

            // Langkah 3-4: buka /kandidat/profil dan /kandidat/lamaran -> dialihkan ke /login.
            foreach (['/kandidat/profil', '/kandidat/lamaran'] as $url) {
                $browser->visit($url)
                    ->waitForLocation('/login')
                    ->assertPathIs('/login')
                    ->assertPresent('@masuk')
                    ->assertMissing('aside.side');
            }
        });
    }
}
