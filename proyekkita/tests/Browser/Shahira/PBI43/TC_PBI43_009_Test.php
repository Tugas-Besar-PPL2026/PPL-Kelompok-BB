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
 * TC-PBI43-009 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-009')]
class TC_PBI43_009_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
      use MenjagaDatabaseE2E;
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI43-009 - Registrasi ditolak saat Email sudah dipakai akun lain.
     */
    public function test_TC_PBI43_009_email_sudah_terdaftar(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            // Precondition: akun Kandidat dengan email ini sudah dibuat lewat registrasi, lalu logout.
            $emailTerdaftar = "rina.terdaftar.{$kode}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Terdaftar', $emailTerdaftar, 'Rahasia123');
            $this->keluar($browser);
            $this->assertSame(1, DB::table('users')->where('email', $emailTerdaftar)->count());

            $isian = [
                'name' => 'Rina Duplikat',
                'email' => $emailTerdaftar,
                'password' => 'Rahasia123',
                'password_confirmation' => 'Rahasia123',
                'terms' => true,
            ];
            $jumlahUserSebelum = DB::table('users')->count();

            // Langkah 1: buka /daftar/kandidat.
            $browser->visit('/daftar/kandidat')->waitFor('@daftar');

            // Langkah 2-3: isi form sesuai input, klik tombol daftar.
            $this->isiFormRegistrasi($browser, $isian)
                ->press('@daftar')
                ->waitFor('@galat-email');

            // Langkah 4: ditolak, tetap di halaman registrasi dengan pesan validasi yang sesuai.
            $browser->assertPathIs('/daftar/kandidat')
                ->assertSeeIn('@galat-email', 'Email sudah terdaftar. Gunakan email lain atau masuk.')
                ->assertInputValue('name', 'Rina Duplikat')
                ->assertInputValue('email', $emailTerdaftar)
                ->assertDontSee('Akun berhasil dibuat.');

            // Tidak ada akun baru yang dibuat dan pengguna tidak login.
            $this->assertSame(1, DB::table('users')->where('email', $emailTerdaftar)->count(), 'Jumlah akun dengan email tersebut harus tetap 1.');
            $this->assertSame($jumlahUserSebelum, DB::table('users')->count(), 'Tidak boleh ada akun baru.');
            $this->assertBelumLogin($browser);
        });
    }

    /** Mengisi form registrasi: string kosong berarti kolom dikosongkan; terms true berarti dicentang. */
    private function isiFormRegistrasi(Browser $browser, array $isian): Browser
    {
        foreach (['name', 'email', 'password', 'password_confirmation'] as $field) {
            $isian[$field] === '' ? $browser->clear($field) : $browser->type($field, $isian[$field]);
        }
        $isian['terms'] ? $browser->check('terms') : $browser->uncheck('terms');

        return $browser;
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
