<?php

namespace Tests\Browser\PBI43;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\AlurE2EProyekKita;
use Tests\Browser\Concerns\MenjagaDatabaseE2E;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * TC-PBI43-001 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
 * Status login diverifikasi lewat perilaku aplikasi: /login khusus tamu (pengguna login dialihkan ke halaman
 * awalnya), halaman terproteksi mengalihkan tamu ke /login. Database hanya dibaca untuk verifikasi tambahan.
 */
#[Group('e2e-auth')]
#[Group('pbi43')]
#[Group('TC-PBI43-001')]
class TC_PBI43_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-001 - Registrasi berhasil sebagai Kandidat dan Nama lengkap otomatis terisi di profil Kandidat.
     */
    public function test_TC_PBI43_001_registrasi_kandidat_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $nama = 'Rina Kandidat';
            $email = "rina.kandidat.{$kode}@proyekkita.test";
            $sandi = 'Rahasia123';

            // Langkah 1-2: buka landing (/), klik 'Daftar' (halaman /daftar/umkm terbuka).
            $browser->visit('/')
                ->click('@nav-daftar')
                ->waitForLocation('/daftar/umkm');

            // Langkah 3-4: pilih peran 'Kandidat', kolom nama berlabel 'Nama lengkap'.
            $browser->click('@peran-kandidat')
                ->waitForLocation('/daftar/kandidat')
                ->assertSeeIn('label[for=name]', 'Nama lengkap');

            // Langkah 5-7: isi form, centang syarat, klik 'Daftar sebagai Kandidat'.
            $browser->type('name', $nama)
                ->type('email', $email)
                ->type('password', $sandi)
                ->type('password_confirmation', $sandi)
                ->check('terms')
                ->assertSeeIn('@daftar', 'Daftar sebagai Kandidat')
                ->press('@daftar');

            // Langkah 8: diarahkan ke Profil & Portofolio dengan pesan berhasil, dalam keadaan login.
            $browser->waitForLocation('/kandidat/profil')
                ->assertSee('Akun berhasil dibuat. Selamat datang di ProyekKita.')
                ->assertSeeIn('h1', 'Profil & Portofolio');

            // Langkah 9: Nama lengkap di Data Diri sudah terisi dari registrasi.
            $this->klikTautan($browser, 'Data Diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->assertInputValue('@nama-lengkap', $nama)
                ->assertSeeIn('#completeness-pct', '0%');
            $this->assertSudahLogin($browser, '/kandidat/profil');

            // Verifikasi database (read-only): data akun sesuai input, password ter-hash, profil belum dibuat.
            $akun = DB::table('users')->where('email', $email)->get();
            $this->assertCount(1, $akun, "Harus tepat 1 user untuk {$email}.");
            $this->assertSame('kandidat', $akun[0]->role);
            $this->assertSame($nama, $akun[0]->name);
            $this->assertNotSame($sandi, $akun[0]->password, 'Password tidak boleh tersimpan sebagai teks asli.');
            $this->assertTrue(Hash::check($sandi, $akun[0]->password), 'Password harus tersimpan sebagai hash.');
            $this->assertSame(0, $this->jumlahProfil('candidate_profiles', $email), 'Profil belum boleh dibuat.');
        });
    }

    /**
     * Verifikasi pengguna SUDAH login: /login khusus tamu, sehingga pengguna login dialihkan ke halaman awalnya.
     */
    private function assertSudahLogin(Browser $browser, string $halamanAwal): void
    {
        $browser->visit('/login')
            ->waitForLocation($halamanAwal)
            ->assertPathIs($halamanAwal)
            ->assertSee('Keluar');
    }

    /** Jumlah baris profil ($tabel) milik akun dengan email ini (read-only). */
    private function jumlahProfil(string $tabel, string $email): int
    {
        return DB::table($tabel)
            ->join('users', 'users.id', '=', "{$tabel}.user_id")
            ->where('users.email', $email)
            ->count();
    }

    /** Nilai satu kolom profil milik akun dengan email ini (read-only). */
    private function kolomProfil(string $tabel, string $email, string $kolom): ?string
    {
        return DB::table($tabel)
            ->join('users', 'users.id', '=', "{$tabel}.user_id")
            ->where('users.email', $email)
            ->value("{$tabel}.{$kolom}");
    }
}
