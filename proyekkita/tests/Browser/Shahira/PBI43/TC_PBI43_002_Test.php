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
 * TC-PBI43-002 (sheet PBI-43, Test_Case_Kelompok_BB_SI4801_1.xlsx).
 *
 * E2E Laravel Dusk: registrasi, login, dan logout dilakukan lewat UI Chrome. Tanpa loginAs/actingAs,
 * tanpa manipulasi session/cookie untuk melewati login, tanpa factory/seeder/SQL tulis.
 * Akun Kandidat/UMKM dibuat lewat halaman registrasi dengan email unik per run.
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
//use UsesDuskTestDatabase;
      

    /**
     * TC-PBI43-002 - Registrasi berhasil sebagai UMKM dan Nama UMKM otomatis terisi di Profil Usaha.
     */
    public function test_TC_PBI43_002_registrasi_umkm_berhasil(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $nama = 'Kopi Budi Nusantara';
            $email = "kopi.budi.{$kode}@proyekkita.test";
            $sandi = 'Rahasia123';

            // Langkah 1-3: buka landing (/), klik 'Daftar', pilih peran 'UMKM'.
            $browser->visit('/')
                ->click('@nav-daftar')
                ->waitForLocation('/daftar/umkm')
                ->click('@peran-umkm')
                ->waitForLocation('/daftar/umkm');

            // Langkah 4: kolom nama berlabel 'Nama UMKM' (bukan 'Nama penanggung jawab') beserta keterangannya.
            $browser->assertSeeIn('label[for=name]', 'Nama UMKM')
                ->assertDontSee('Nama penanggung jawab')
                ->assertSee('Nama ini otomatis terisi sebagai Nama usaha di Profil Usaha.');

            // Langkah 5-7: isi form, centang syarat, klik 'Daftar sebagai UMKM'.
            $browser->type('name', $nama)
                ->type('email', $email)
                ->type('password', $sandi)
                ->type('password_confirmation', $sandi)
                ->check('terms')
                ->assertSeeIn('@daftar', 'Daftar sebagai UMKM')
                ->press('@daftar');

            // Langkah 8: Profil Usaha dengan pesan berhasil, Nama usaha terisi, status 'Belum diisi'.
            $browser->waitForLocation('/umkm/profil')
                ->assertSee('Akun berhasil dibuat. Selamat datang di ProyekKita.')
                ->assertSeeIn('h1', 'Profil Usaha')
                ->assertInputValue('@nama-usaha', $nama)
                ->assertSeeIn('@status-verifikasi', 'Belum diisi');
            $this->assertSudahLogin($browser, '/umkm/profil');

            // Verifikasi database (read-only): data akun sesuai input, password ter-hash, profil belum dibuat.
            $akun = DB::table('users')->where('email', $email)->get();
            $this->assertCount(1, $akun, "Harus tepat 1 user untuk {$email}.");
            $this->assertSame('umkm', $akun[0]->role);
            $this->assertSame($nama, $akun[0]->name);
            $this->assertNotSame($sandi, $akun[0]->password, 'Password tidak boleh tersimpan sebagai teks asli.');
            $this->assertTrue(Hash::check($sandi, $akun[0]->password), 'Password harus tersimpan sebagai hash.');
            $this->assertSame(0, $this->jumlahProfil('umkm_profiles', $email), 'Profil usaha belum boleh dibuat.');
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
