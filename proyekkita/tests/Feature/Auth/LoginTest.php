<?php

namespace Tests\Feature\Auth;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * [FR-01] Masuk (login), pembatasan percobaan, redirect berdasarkan peran, dan keluar (logout).
 */
class LoginTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    private const PESAN_GAGAL = 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.';

    private function masuk(string $email, string $password = 'password')
    {
        return $this->post(route('login.attempt'), ['email' => $email, 'password' => $password]);
    }

    // ---------------------------------------------------------------- tampilan

    public function test_halaman_masuk_tampil_untuk_tamu(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Gunakan email dan kata sandi yang terdaftar.')
            ->assertSee('Akun Admin disediakan oleh sistem dan tidak dapat didaftarkan.')
            ->assertSee(route('register', ['role' => 'umkm']), false)
            ->assertSee(route('register', ['role' => 'kandidat']), false);
    }

    // ------------------------------------------------- login berhasil per peran

    public function test_admin_masuk_dan_diarahkan_ke_data_master(): void
    {
        $admin = User::factory()->admin()->create();

        $this->masuk($admin->email)->assertRedirect(route('admin.master.index'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_umkm_masuk_dan_diarahkan_ke_profil_usaha(): void
    {
        $umkm = User::factory()->umkm()->create();

        $this->masuk($umkm->email)->assertRedirect(route('umkm.profil.edit'));
        $this->assertAuthenticatedAs($umkm);
    }

    public function test_kandidat_masuk_dan_diarahkan_ke_profil_dan_portofolio(): void
    {
        $kandidat = User::factory()->kandidat()->create();

        $this->masuk($kandidat->email)->assertRedirect(route('kandidat.profil.edit'));
        $this->assertAuthenticatedAs($kandidat);
    }

    public function test_email_tidak_membedakan_huruf_besar_kecil(): void
    {
        $kandidat = User::factory()->kandidat()->create(['email' => 'fajar.pratama@contoh.test']);

        $this->masuk('  Fajar.Pratama@Contoh.TEST ')->assertRedirect(route('kandidat.profil.edit'));
        $this->assertAuthenticatedAs($kandidat);
    }

    /** FR-01: UMKM yang belum terverifikasi tetap dapat masuk, dan status verifikasi tidak diubah oleh login. */
    public function test_umkm_pending_tetap_dapat_masuk_tanpa_mengubah_status_verifikasi(): void
    {
        $umkm = $this->makeUmkm(['verification_status' => VerificationStatus::Pending]);

        $this->masuk($umkm->email)->assertRedirect(route('umkm.profil.edit'));
        $this->assertAuthenticatedAs($umkm);
        $this->assertSame(VerificationStatus::Pending, $umkm->umkmProfile()->first()->verification_status);
    }

    // ------------------------------------------------------------ login gagal

    public function test_kata_sandi_salah_ditolak_dengan_pesan_umum(): void
    {
        $user = User::factory()->umkm()->create();

        $this->from(route('login'))->masuk($user->email, 'salah-total')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['credentials' => self::PESAN_GAGAL]);

        $this->assertGuest();
    }

    public function test_email_tidak_terdaftar_memakai_pesan_yang_sama(): void
    {
        $this->from(route('login'))->masuk('tidak.ada@contoh.test')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['credentials' => self::PESAN_GAGAL]);

        $this->assertGuest();
    }

    public function test_email_tetap_terisi_dan_kata_sandi_tidak_dikembalikan_setelah_gagal(): void
    {
        $user = User::factory()->umkm()->create();

        $this->from(route('login'))->masuk($user->email, 'salah-total');
        $html = $this->get(route('login'))->assertOk()->assertSee(self::PESAN_GAGAL)->getContent();

        $this->assertStringContainsString('value="'.$user->email.'"', $html);
        $this->assertStringNotContainsString('salah-total', $html);
    }

    public function test_isian_kosong_menghasilkan_galat_validasi(): void
    {
        $this->from(route('login'))->post(route('login.attempt'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Email wajib diisi.',
                'password' => 'Kata sandi wajib diisi.',
            ]);

        $this->assertGuest();
    }

    public function test_format_email_tidak_valid_ditolak(): void
    {
        $this->from(route('login'))->masuk('bukan-email')
            ->assertSessionHasErrors(['email' => 'Format email tidak valid.']);

        $this->assertGuest();
    }

    // ------------------------------------------------------ pembatasan percobaan

    public function test_percobaan_ke_enam_ditolak_sementara_walau_kata_sandi_benar(): void
    {
        $user = User::factory()->kandidat()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->masuk($user->email, 'salah-total');
        }

        $this->masuk($user->email, 'password')->assertSessionHasErrors('credentials');

        $this->assertStringContainsString('Terlalu banyak percobaan masuk', session('errors')->first('credentials'));
        $this->assertGuest();
    }

    public function test_pembatasan_hanya_berlaku_untuk_email_yang_dicoba(): void
    {
        $dibatasi = User::factory()->kandidat()->create();
        $lain = User::factory()->umkm()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->masuk($dibatasi->email, 'salah-total');
        }

        $this->masuk($lain->email)->assertRedirect(route('umkm.profil.edit'));
        $this->assertAuthenticatedAs($lain);
    }

    public function test_login_berhasil_mereset_hitungan_percobaan_gagal(): void
    {
        $user = User::factory()->kandidat()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->masuk($user->email, 'salah-total');
        }
        $this->masuk($user->email)->assertRedirect(route('kandidat.profil.edit'));
        $this->post(route('logout'));

        for ($i = 0; $i < 4; $i++) {
            $this->masuk($user->email, 'salah-total');
        }

        $this->masuk($user->email)->assertRedirect(route('kandidat.profil.edit'));
        $this->assertAuthenticatedAs($user);
    }

    // ------------------------------------------ halaman khusus tamu bagi yang sudah login

    public function test_pengguna_yang_sudah_login_dialihkan_dari_halaman_tamu_ke_halaman_perannya(): void
    {
        $peran = [
            'admin' => 'admin.master.index',
            'umkm' => 'umkm.profil.edit',
            'kandidat' => 'kandidat.profil.edit',
        ];

        foreach ($peran as $role => $home) {
            $user = User::factory()->{$role}()->create();

            foreach ([route('landing'), route('login'), route('register', ['role' => 'umkm']), route('register', ['role' => 'kandidat'])] as $url) {
                $this->actingAs($user)->get($url)->assertRedirect(route($home));
            }
        }
    }

    // ------------------------------------------------------------------- logout

    public function test_logout_mengakhiri_sesi_dan_kembali_ke_landing(): void
    {
        $user = User::factory()->kandidat()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('landing'));

        $this->assertGuest();
        $this->get(route('kandidat.profil.edit'))->assertRedirect(route('login'));
    }

    public function test_logout_tanpa_login_dialihkan_ke_halaman_masuk(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_logout_hanya_menerima_method_post(): void
    {
        $this->actingAs(User::factory()->umkm()->create())->get('/logout')->assertStatus(405);
    }
}
