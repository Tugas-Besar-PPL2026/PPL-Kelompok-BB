<?php

namespace Tests\Browser\PBI43;

use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI43-023 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-023')]
class TC_PBI43_023_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-023 - Pengguna yang sudah login dialihkan saat membuka halaman registrasi.
     */
    public function test_TC_PBI43_023_pengguna_login_dialihkan_dari_halaman_registrasi(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $email = "rina.login.{$kode}@proyekkita.test";

            // Precondition: login sebagai Kandidat (akun dibuat lewat registrasi).
            $this->daftar($browser, 'kandidat', 'Rina Sudah Login', $email, 'Rahasia123');
            $jumlahUserSebelum = DB::table('users')->count();

            // Langkah 1-4: /daftar/kandidat dan /daftar/umkm mengalihkan ke halaman awal Kandidat.
            foreach (['/daftar/kandidat', '/daftar/umkm'] as $url) {
                $browser->visit($url)
                    ->waitForLocation('/kandidat/profil')
                    ->assertPathIs('/kandidat/profil')
                    ->assertSeeIn('h1', 'Profil & Portofolio')
                    ->assertMissing('@daftar')
                    ->assertSee('Keluar');
            }

            $this->assertSame($jumlahUserSebelum, DB::table('users')->count(), 'Tidak boleh ada akun baru.');
        });
    }
}
