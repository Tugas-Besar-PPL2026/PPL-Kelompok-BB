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
 * TC-PBI43-003 (sheet PBI-43, Test_Case_ProyekKita_Revisi_PBI16-18_PBI43-45.xlsx).
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
#[Group('TC-PBI43-003')]
class TC_PBI43_003_Test extends DuskTestCase
{
    use AlurE2EProyekKita;
    use MenjagaDatabaseE2E;
    use UsesDuskTestDatabase;

    /**
     * TC-PBI43-003 - Registrasi gagal karena input wajib kosong atau tidak valid.
     */
    public function test_TC_PBI43_003_registrasi_gagal_input_kosong_atau_tidak_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $kode = $this->kodeUnik();
            $valid = [
                'name' => 'Rina Validasi',
                'email' => "rina.validasi.{$kode}@proyekkita.test",
                'password' => 'Rahasia123',
                'password_confirmation' => 'Rahasia123',
                'terms' => true,
            ];
            $jumlahUserSebelum = DB::table('users')->count();

            // Variasi sesuai kolom Input Excel => [isian, pesan yang diharapkan per selector galat].
            $variasi = [
                'a' => [
                    ['name' => '', 'email' => '', 'password' => '', 'password_confirmation' => '', 'terms' => false],
                    [
                        '@galat-name' => 'Nama lengkap wajib diisi.',
                        '@galat-email' => 'Email wajib diisi.',
                        '@galat-password' => 'Kata sandi wajib diisi.',
                        '@galat-terms' => 'Anda harus menyetujui syarat dan ketentuan penggunaan ProyekKita.',
                    ],
                ],
                'b' => [['email' => ''] + $valid, ['@galat-email' => 'Email wajib diisi.']],
                'c' => [['password' => ''] + $valid, ['@galat-password' => 'Kata sandi wajib diisi.']],
                'd' => [['password_confirmation' => ''] + $valid, ['@galat-password' => 'Konfirmasi kata sandi tidak sama.']],
                'e' => [['email' => 'rina.proyekkita'] + $valid, ['@galat-email' => 'Format email tidak valid.']],
                'f' => [['password' => 'Abc1234', 'password_confirmation' => 'Abc1234'] + $valid, ['@galat-password' => 'Kata sandi minimal 8 karakter.']],
            ];

            foreach ($variasi as $kodeVariasi => [$isian, $pesan]) {
                // Langkah 1: buka /daftar/kandidat (form diisi ulang setiap variasi).
                $browser->visit('/daftar/kandidat')->waitFor('@daftar');

                // Langkah 2: isi form sesuai variasi, klik 'Daftar sebagai Kandidat'.
                foreach (['name', 'email', 'password', 'password_confirmation'] as $field) {
                    $isian[$field] === '' ? $browser->clear($field) : $browser->type($field, $isian[$field]);
                }
                $isian['terms'] ? $browser->check('terms') : $browser->uncheck('terms');

                $browser->press('@daftar')->waitFor(array_key_first($pesan));

                // Ditolak: tetap di halaman registrasi dan pesan validasi sesuai.
                $browser->assertPathIs('/daftar/kandidat');
                foreach ($pesan as $selector => $teks) {
                    $browser->assertSeeIn($selector, $teks);
                }
                $browser->assertDontSee('Akun berhasil dibuat.');
            }

            // Langkah 4: tidak ada akun baru yang dibuat dan pengguna tidak login.
            $this->assertSame(0, DB::table('users')->where('email', $valid['email'])->count());
            $this->assertSame($jumlahUserSebelum, DB::table('users')->count(), 'Tidak boleh ada akun baru.');
            $this->assertBelumLogin($browser);
        });
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
