<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * [FR-01] Registrasi UMKM dan Kandidat: pembuatan akun, hashing kata sandi, validasi, dan keamanan peran.
 */
class RegisterTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Rina Kartika',
            'email' => 'rina@kopinusantara.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'terms' => '1',
        ], $override);
    }

    private function daftar(string $role, array $override = [])
    {
        return $this->post(route('register.store', ['role' => $role]), $this->payload($override));
    }

    // ---------------------------------------------------------------- tampilan

    public function test_halaman_registrasi_umkm_tampil(): void
    {
        $this->get(route('register', ['role' => 'umkm']))
            ->assertOk()
            ->assertSee('Daftar sebagai UMKM')
            ->assertSee('Nama penanggung jawab')
            ->assertSee('Minimal 8 karakter.')
            ->assertSee('Admin memverifikasi akun UMKM sebelum Anda dapat mengajukan proyek');
    }

    public function test_halaman_registrasi_kandidat_tampil(): void
    {
        $this->get(route('register', ['role' => 'kandidat']))
            ->assertOk()
            ->assertSee('Daftar sebagai Kandidat')
            ->assertSee('Nama lengkap')
            ->assertSee('Setelah mendaftar, Anda diarahkan ke Profil &amp; Portofolio', false);
    }

    public function test_peran_selain_umkm_dan_kandidat_tidak_tersedia_termasuk_admin(): void
    {
        $this->get('/daftar/admin')->assertNotFound();
        $this->post('/daftar/admin', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    // ----------------------------------------------------------- registrasi UMKM

    public function test_umkm_berhasil_mendaftar_login_otomatis_dan_diarahkan_ke_profil_usaha(): void
    {
        $this->daftar('umkm')->assertRedirect(route('umkm.profil.edit'));

        $user = User::where('email', 'rina@kopinusantara.test')->firstOrFail();
        $this->assertSame(UserRole::Umkm, $user->role);
        $this->assertSame('Rina Kartika', $user->name);
        $this->assertAuthenticatedAs($user);

        // Halaman tujuan harus dapat dibuka untuk akun baru yang belum punya profil usaha.
        $this->get(route('umkm.profil.edit'))->assertOk()->assertSee('Akun berhasil dibuat.');
    }

    public function test_registrasi_umkm_tidak_membuat_profil_dan_tidak_menyentuh_status_verifikasi(): void
    {
        $this->daftar('umkm');

        $user = User::where('email', 'rina@kopinusantara.test')->firstOrFail();
        $this->assertNull($user->umkmProfile()->first());
        $this->assertDatabaseCount('umkm_profiles', 0);
    }

    // -------------------------------------------------------- registrasi Kandidat

    public function test_kandidat_berhasil_mendaftar_dengan_email_domain_apa_pun(): void
    {
        $this->daftar('kandidat', ['name' => 'Fajar Pratama', 'email' => 'fajar@gmail.com'])
            ->assertRedirect(route('kandidat.profil.edit'));

        $user = User::where('email', 'fajar@gmail.com')->firstOrFail();
        $this->assertSame(UserRole::Kandidat, $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('candidate_profiles', 0);

        $this->get(route('kandidat.profil.edit'))->assertOk();
    }

    // --------------------------------------------------------------- keamanan

    public function test_kata_sandi_disimpan_dalam_bentuk_hash_dan_tidak_di_hash_ganda(): void
    {
        $this->daftar('kandidat');

        $user = User::where('email', 'rina@kopinusantara.test')->firstOrFail();
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_akun_yang_baru_didaftarkan_dapat_masuk_lagi_dengan_kata_sandinya(): void
    {
        $this->daftar('umkm');
        $this->post(route('logout'));

        $this->post(route('login.attempt'), ['email' => 'rina@kopinusantara.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('umkm.profil.edit'));
    }

    public function test_peran_dari_input_diabaikan_sehingga_admin_tidak_bisa_dibuat_lewat_registrasi(): void
    {
        $this->daftar('umkm', ['role' => 'admin']);
        $this->assertSame(UserRole::Umkm, User::firstOrFail()->role);

        $this->post(route('logout'));
        $this->daftar('kandidat', ['email' => 'lain@contoh.test', 'role' => 'admin']);
        $this->assertSame(UserRole::Kandidat, User::where('email', 'lain@contoh.test')->firstOrFail()->role);
        $this->assertSame(0, User::where('role', UserRole::Admin->value)->count());
    }

    public function test_email_diperlakukan_tanpa_membedakan_huruf_besar_kecil_dan_disimpan_huruf_kecil(): void
    {
        $this->daftar('umkm', ['email' => '  Rina@KopiNusantara.TEST ']);

        $this->assertSame(1, User::where('email', 'rina@kopinusantara.test')->count());
    }

    // --------------------------------------------------------------- validasi

    public function test_email_yang_sudah_terdaftar_ditolak_walau_beda_huruf_besar(): void
    {
        User::factory()->kandidat()->create(['email' => 'rina@kopinusantara.test']);

        $this->from(route('register', ['role' => 'umkm']))
            ->daftar('umkm', ['email' => 'RINA@kopinusantara.test'])
            ->assertRedirect(route('register', ['role' => 'umkm']))
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Gunakan email lain atau masuk.']);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_isian_wajib_kosong_ditolak(): void
    {
        $this->post(route('register.store', ['role' => 'umkm']), [])
            ->assertSessionHasErrors([
                'name' => 'Nama penanggung jawab wajib diisi.',
                'email' => 'Email wajib diisi.',
                'password' => 'Kata sandi wajib diisi.',
                'terms' => 'Anda harus menyetujui syarat dan ketentuan penggunaan ProyekKita.',
            ]);

        $this->post(route('register.store', ['role' => 'kandidat']), [])
            ->assertSessionHasErrors(['name' => 'Nama lengkap wajib diisi.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_kata_sandi_kurang_dari_8_karakter_ditolak_dan_tepat_8_diterima(): void
    {
        $this->daftar('kandidat', ['password' => 'pendek1', 'password_confirmation' => 'pendek1'])
            ->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
        $this->assertDatabaseCount('users', 0);

        $this->daftar('kandidat', ['password' => '12345678', 'password_confirmation' => '12345678'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_konfirmasi_kata_sandi_harus_sama(): void
    {
        $this->daftar('umkm', ['password_confirmation' => 'berbeda123'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi tidak sama.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_syarat_dan_ketentuan_wajib_dicentang(): void
    {
        $data = $this->payload();
        unset($data['terms']);

        $this->post(route('register.store', ['role' => 'umkm']), $data)
            ->assertSessionHasErrors(['terms' => 'Anda harus menyetujui syarat dan ketentuan penggunaan ProyekKita.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_format_email_dan_batas_panjang_divalidasi(): void
    {
        $this->daftar('umkm', ['email' => 'bukan-email'])->assertSessionHasErrors('email');
        $this->daftar('umkm', ['name' => str_repeat('a', 101)])->assertSessionHasErrors('name');
        $this->daftar('umkm', ['email' => str_repeat('a', 145).'@x.test'])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registrasi_tidak_mengubah_status_verifikasi_umkm_lain(): void
    {
        $lain = $this->makeUmkm(['verification_status' => VerificationStatus::Verified]);

        $this->daftar('umkm');

        $this->assertSame(VerificationStatus::Verified, $lain->umkmProfile()->first()->verification_status);
    }
}
