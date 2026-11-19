<?php

namespace Tests\Browser\PBI45;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI45-001 (sheet PBI-45, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
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
#[Group('TC-PBI45-001')]
class TC_PBI45_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
     

    /**
     * TC-PBI45-001 - Pengguna berhasil logout lewat tombol 'Keluar'.
     */
    public function test_TC_PBI45_001_logout_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat terdaftar lewat registrasi, lalu login lewat /login (ukuran desktop).
            $akun = ['email' => "rina.auth.{$this->kodeUnik()}@proyekkita.test", 'sandi' => 'Rahasia123'];
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $akun['email'], $akun['sandi']);
            $this->keluar($browser);
            $this->masukSebagaiKandidat($browser, $akun);

            // Langkah 1: halaman Profil & Portofolio tampil dengan menu samping.
            $browser->assertPathIs('/kandidat/profil')
                ->assertSeeIn('h1', 'Profil & Portofolio')
                ->assertVisible('aside.side');

            // Langkah 2: klik 'Keluar' di bagian bawah menu samping.
            $browser->press('Keluar');

            // Langkah 3: diarahkan ke landing (/) dengan tombol 'Masuk' dan 'Daftar'.
            $browser->waitForLocation('/')
                ->assertPathIs('/')
                ->assertSeeIn('@nav-masuk', 'Masuk')
                ->assertSeeIn('@nav-daftar', 'Daftar')
                ->assertMissing('aside.side');

            // Langkah 4: buka /login -> form login tampil (sesi berakhir, tidak dialihkan ke halaman profil).
            $browser->visit('/login')
                ->assertPathIs('/login')
                ->assertPresent('@masuk');

            // Verifikasi tambahan sesi berakhir: halaman terproteksi mengalihkan ke /login.
            $browser->visit('/kandidat/profil')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
