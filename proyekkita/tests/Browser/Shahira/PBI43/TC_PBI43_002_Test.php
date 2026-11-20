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
 * TC-PBI43-002 (sheet PBI-43, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run. Akun Admin memakai akun
 * yang sudah disiapkan satu kali di database test (DUSK_ADMIN_EMAIL / DUSK_ADMIN_PASSWORD).
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-002')]
class TC_PBI43_002_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-002 - Registrasi gagal karena email sudah terdaftar.
     */
    public function test_TC_PBI43_002_registrasi_gagal_email_sudah_terdaftar(): void
    {
        $this->browse(function (Browser $browser) {
            // Precondition: akun Kandidat sudah dibuat lewat halaman registrasi, lalu logout.
            $kode = $this->kodeUnik();
            $email = "rina.auth.{$kode}@proyekkita.test";
            $this->daftar($browser, 'kandidat', 'Rina Kandidat', $email, 'Rahasia123');
            $this->keluar($browser);
            $this->assertSame(1, $this->jumlahAkun($email));

            // Input A: email sama persis. Input B: diawali spasi dan huruf besar.
            $percobaan = ['A' => $email, 'B' => '  '.mb_strtoupper($email)];

            foreach ($percobaan as $label => $emailUji) {
                // Langkah 1: buka /daftar/kandidat.
                $browser->visit('/daftar/kandidat')->waitFor('@daftar');

                // Langkah 2-3: isi Nama, Kata sandi, Konfirmasi, centang syarat, isi Email, klik Daftar.
                $browser->type('name', 'Rina Duplikat')
                    ->type('email', $emailUji)
                    ->type('password', 'Rahasia123')
                    ->type('password_confirmation', 'Rahasia123')
                    ->check('terms')
                    ->press('@daftar')
                    ->waitFor('@galat-email');

                // Ditolak: tetap di halaman registrasi dengan pesan email sudah terdaftar.
                $browser->assertPathIs('/daftar/kandidat')
                    ->assertSeeIn('@galat-email', 'Email sudah terdaftar. Gunakan email lain atau masuk.')
                    ->assertInputValue('name', 'Rina Duplikat');

                // Email tetap terisi (aplikasi mengembalikan email yang sudah dinormalisasi: tanpa spasi, huruf kecil).
                $this->assertSame(
                    mb_strtolower(trim($emailUji)),
                    mb_strtolower(trim((string) $browser->inputValue('email'))),
                    "Email percobaan {$label} harus tetap terisi."
                );

                // Pengguna tidak login.
                $this->assertBelumLogin($browser);
            }

            // Langkah 5: jumlah akun dengan email tersebut tetap 1.
            $this->assertSame(1, $this->jumlahAkun($email));
        });
    }

    /** Jumlah akun dengan email ini (read-only). */
    private function jumlahAkun(string $email): int
    {
        return DB::table('users')->where('email', mb_strtolower(trim($email)))->count();
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
