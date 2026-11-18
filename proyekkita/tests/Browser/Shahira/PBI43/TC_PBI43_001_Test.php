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
 * TC-PBI43-001 (sheet PBI-43, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
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
#[Group('TC-PBI43-001')]
class TC_PBI43_001_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-001 - Registrasi berhasil dengan data valid sebagai Kandidat dan UMKM.
     */
    public function test_TC_PBI43_001_registrasi_berhasil_kandidat_dan_umkm(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $kandidat = ['nama' => 'Rina Kandidat', 'email' => "rina.auth.{$kode}@proyekkita.test"];
            $umkm = ['nama' => 'Budi UMKM', 'email' => "budi.auth.{$kode}@proyekkita.test"];
            $sandi = 'Rahasia123';

            // Langkah 1-2: buka landing (/), klik 'Daftar' (halaman /daftar/umkm terbuka).
            $browser->visit('/')
                ->click('@nav-daftar')
                ->waitForLocation('/daftar/umkm');

            // Langkah 3: pilih peran 'Kandidat'.
            $browser->click('@peran-kandidat')
                ->waitForLocation('/daftar/kandidat')
                ->assertSee('Nama lengkap');

            // Langkah 4-6: isi form Kandidat, centang syarat, klik 'Daftar sebagai Kandidat'.
            $browser->type('name', $kandidat['nama'])
                ->type('email', $kandidat['email'])
                ->type('password', $sandi)
                ->type('password_confirmation', $sandi)
                ->check('terms')
                ->assertSeeIn('@daftar', 'Daftar sebagai Kandidat')
                ->press('@daftar');

            // Langkah 7: halaman tujuan dan pesan.
            $browser->waitForLocation('/kandidat/profil')
                ->assertSee('Akun berhasil dibuat. Selamat datang di ProyekKita.')
                ->assertSeeIn('h1', 'Profil & Portofolio');
            $this->assertSudahLogin($browser, '/kandidat/profil');

            // Langkah 8: klik 'Keluar' di menu samping.
            $browser->press('Keluar')->waitForLocation('/');

            // Langkah 9: dari landing klik 'Daftar' (peran UMKM terpilih), isi form UMKM, klik 'Daftar sebagai UMKM'.
            $browser->click('@nav-daftar')
                ->waitForLocation('/daftar/umkm')
                ->assertSee('Nama penanggung jawab')
                ->type('name', $umkm['nama'])
                ->type('email', $umkm['email'])
                ->type('password', $sandi)
                ->type('password_confirmation', $sandi)
                ->check('terms')
                ->assertSeeIn('@daftar', 'Daftar sebagai UMKM')
                ->press('@daftar');

            // Langkah 10: halaman tujuan dan pesan.
            $browser->waitForLocation('/umkm/profil')
                ->assertSee('Akun berhasil dibuat. Selamat datang di ProyekKita.')
                ->assertSeeIn('h1', 'Profil Usaha');
            $this->assertSudahLogin($browser, '/umkm/profil');

            // Verifikasi database (read-only): tepat 1 user per email, role sesuai, password ter-hash.
            foreach ([[$kandidat['email'], 'kandidat'], [$umkm['email'], 'umkm']] as [$email, $role]) {
                $akun = DB::table('users')->where('email', $email)->get();
                $this->assertCount(1, $akun, "Harus tepat 1 user untuk {$email}.");
                $this->assertSame($role, $akun[0]->role);
                $this->assertNotSame($sandi, $akun[0]->password, 'Password tidak boleh tersimpan sebagai teks asli.');
                $this->assertTrue(Hash::check($sandi, $akun[0]->password), 'Password harus tersimpan sebagai hash dari kata sandi yang diisi.');
            }
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
}
