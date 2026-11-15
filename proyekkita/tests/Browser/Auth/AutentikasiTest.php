<?php

namespace Tests\Browser\Auth;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesDuskTestDatabase;
use Tests\DuskTestCase;

/**
 * [FR-01] Autentikasi, registrasi, dan otorisasi melalui browser.
 * Kode skenario DA-xx dipakai di laporan pengujian. Database: lihat UsesDuskTestDatabase (database/dusk.sqlite).
 * Kata sandi semua akun uji dari UserFactory adalah "password".
 */
class AutentikasiTest extends DuskTestCase
{
    use BuildsApplicationScenario, UsesDuskTestDatabase;

    private const PESAN_GAGAL = 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.';

    private const PESAN_403 = 'Anda tidak memiliki akses ke halaman ini.';

    private function isiMasuk(Browser $browser, string $email, string $password): Browser
    {
        return $browser->visit('/login')
            ->type('email', $email)
            ->type('password', $password)
            ->press('@masuk');
    }

    // =====================================================================
    // Landing page
    // =====================================================================

    /** DA-01 */
    public function test_landing_page_tampil_untuk_tamu_dan_tombol_mengarah_ke_halaman_yang_benar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPathIs('/')
                ->assertSee('Cara kerja ProyekKita')
                ->assertSee('Proyek jangka pendek UMKM')
                ->click('@hero-daftar-umkm')
                ->assertPathIs('/daftar/umkm')
                ->back()
                ->click('@hero-daftar-kandidat')
                ->assertPathIs('/daftar/kandidat')
                ->back()
                ->click('@nav-masuk')
                ->assertPathIs('/login');
        });
    }

    // =====================================================================
    // Login
    // =====================================================================

    /** DA-02 */
    public function test_admin_masuk_diarahkan_ke_data_master(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@proyekkita.test']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->isiMasuk($browser, $admin->email, 'password')
                ->waitForLocation('/admin/data-master')
                ->assertAuthenticatedAs($admin);
        });
    }

    /** DA-03 */
    public function test_umkm_masuk_diarahkan_ke_profil_usaha(): void
    {
        $umkm = User::factory()->umkm()->create();

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->isiMasuk($browser, $umkm->email, 'password')
                ->waitForLocation('/umkm/profil')
                ->assertAuthenticatedAs($umkm);
        });
    }

    /** DA-04 */
    public function test_kandidat_masuk_diarahkan_ke_profil_dan_portofolio(): void
    {
        $kandidat = User::factory()->kandidat()->create();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $this->isiMasuk($browser, $kandidat->email, 'password')
                ->waitForLocation('/kandidat/profil')
                ->assertAuthenticatedAs($kandidat);
        });
    }

    /** DA-05: FR-01, UMKM Menunggu Pemeriksaan tetap dapat masuk dan statusnya tidak berubah. */
    public function test_umkm_pending_tetap_dapat_masuk(): void
    {
        $umkm = $this->makeUmkm(['verification_status' => VerificationStatus::Pending]);

        $this->browse(function (Browser $browser) use ($umkm) {
            $this->isiMasuk($browser, $umkm->email, 'password')
                ->waitForLocation('/umkm/profil')
                ->assertAuthenticatedAs($umkm);
        });

        $this->assertSame(VerificationStatus::Pending, $umkm->umkmProfile()->first()->verification_status);
    }

    /** DA-06 */
    public function test_kata_sandi_salah_menampilkan_pesan_dan_tetap_di_halaman_masuk(): void
    {
        $user = User::factory()->umkm()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $this->isiMasuk($browser, $user->email, 'salah-total')
                ->waitFor('@galat-login')
                ->assertSeeIn('@galat-login', self::PESAN_GAGAL)
                ->assertPathIs('/login')
                ->assertInputValue('email', $user->email)
                ->assertInputValue('password', '')
                ->assertGuest();
        });
    }

    /** DA-07 */
    public function test_email_tidak_terdaftar_menampilkan_pesan_yang_sama(): void
    {
        $this->browse(function (Browser $browser) {
            $this->isiMasuk($browser, 'tidak.ada@contoh.test', 'password')
                ->waitFor('@galat-login')
                ->assertSeeIn('@galat-login', self::PESAN_GAGAL)
                ->assertGuest();
        });
    }

    /** DA-08 */
    public function test_isian_masuk_kosong_menampilkan_pesan_validasi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->press('@masuk')
                ->waitFor('@galat-email')
                ->assertSeeIn('@galat-email', 'Email wajib diisi.')
                ->assertSeeIn('@galat-password', 'Kata sandi wajib diisi.')
                ->assertGuest();
        });
    }

    /** DA-09 */
    public function test_tombol_mata_menampilkan_dan_menyembunyikan_kata_sandi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertAttribute('#password', 'type', 'password')
                ->click('@tampilkan-sandi')
                ->assertAttribute('#password', 'type', 'text')
                ->click('@tampilkan-sandi')
                ->assertAttribute('#password', 'type', 'password');
        });
    }

    // =====================================================================
    // Registrasi
    // =====================================================================

    /** DA-10 */
    public function test_registrasi_umkm_berhasil_login_otomatis_dan_ke_profil_usaha(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/daftar/umkm')
                ->type('name', 'Rina Kartika')
                ->type('email', 'rina@kopinusantara.test')
                ->type('password', 'rahasia123')
                ->type('password_confirmation', 'rahasia123')
                ->check('terms')
                ->press('@daftar')
                ->waitForLocation('/umkm/profil')
                ->assertSee('Akun berhasil dibuat.');
        });

        $user = User::where('email', 'rina@kopinusantara.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Umkm, $user->role);
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertNull($user->umkmProfile()->first());
    }

    /** DA-11 */
    public function test_registrasi_kandidat_berhasil_dengan_email_domain_apa_pun(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/daftar/kandidat')
                ->type('name', 'Fajar Pratama')
                ->type('email', 'fajar.pratama@gmail.com')
                ->type('password', 'rahasia123')
                ->type('password_confirmation', 'rahasia123')
                ->check('terms')
                ->press('@daftar')
                ->waitForLocation('/kandidat/profil')
                ->assertSee('Akun berhasil dibuat.');
        });

        $user = User::where('email', 'fajar.pratama@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Kandidat, $user->role);
    }

    /** DA-12 */
    public function test_registrasi_gagal_menampilkan_pesan_validasi_dan_tidak_membuat_akun(): void
    {
        User::factory()->kandidat()->create(['email' => 'sudah@ada.test']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/daftar/kandidat')
                ->type('name', 'Fajar Pratama')
                ->type('email', 'sudah@ada.test')
                ->type('password', 'pendek1')
                ->type('password_confirmation', 'beda1234')
                ->press('@daftar')
                ->waitFor('@galat-email')
                ->assertSeeIn('@galat-email', 'Email sudah terdaftar.')
                ->assertSeeIn('@galat-password', 'Kata sandi minimal 8 karakter.')
                ->assertSeeIn('@galat-terms', 'Anda harus menyetujui syarat dan ketentuan')
                ->assertInputValue('name', 'Fajar Pratama')
                ->assertGuest();
        });

        $this->assertSame(1, User::count());
    }

    /** DA-13 */
    public function test_pilihan_peran_berpindah_antar_halaman_registrasi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/daftar/umkm')
                ->assertSee('Nama penanggung jawab')
                ->click('@peran-kandidat')
                ->assertPathIs('/daftar/kandidat')
                ->assertSee('Nama lengkap')
                ->click('@peran-umkm')
                ->assertPathIs('/daftar/umkm');
        });
    }

    /** DA-14 */
    public function test_peran_admin_tidak_tersedia_untuk_registrasi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/daftar/admin')->assertSourceHas('Not Found');
        });
    }

    // =====================================================================
    // Logout
    // =====================================================================

    /** DA-15 */
    public function test_logout_mengakhiri_sesi_dan_halaman_privat_tidak_dapat_dibuka_lagi(): void
    {
        $kandidat = User::factory()->kandidat()->create();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil')
                ->press('Keluar')
                ->waitForLocation('/')
                ->assertGuest()
                ->visit('/kandidat/profil')
                ->assertPathIs('/login');
        });
    }

    // =====================================================================
    // Akses tanpa login dan role yang salah
    // =====================================================================

    /** DA-16 */
    public function test_tamu_yang_membuka_halaman_privat_diarahkan_ke_halaman_masuk(): void
    {
        $this->browse(function (Browser $browser) {
            foreach (['/admin/data-master', '/umkm/profil', '/umkm/proyek/buat', '/kandidat/profil', '/kandidat/proyek'] as $url) {
                $browser->visit($url)->assertPathIs('/login');
            }
        });
    }

    /** DA-17 */
    public function test_kandidat_ditolak_403_di_halaman_admin_dan_umkm(): void
    {
        $kandidat = User::factory()->kandidat()->create();

        $this->browse(function (Browser $browser) use ($kandidat) {
            $browser->loginAs($kandidat);

            foreach (['/admin/data-master', '/admin/moderasi-proyek', '/umkm/profil', '/umkm/proyek/buat'] as $url) {
                $browser->visit($url)->assertSourceHas(self::PESAN_403);
            }
        });
    }

    /** DA-18 */
    public function test_umkm_ditolak_403_di_halaman_admin_dan_kandidat(): void
    {
        $umkm = $this->makeUmkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm);

            foreach (['/admin/data-master', '/admin/moderasi-proyek', '/kandidat/profil', '/kandidat/proyek'] as $url) {
                $browser->visit($url)->assertSourceHas(self::PESAN_403);
            }
        });
    }

    /** DA-19 */
    public function test_admin_ditolak_403_di_halaman_umkm_dan_kandidat(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin);

            foreach (['/umkm/proyek/buat', '/umkm/seleksi', '/kandidat/profil', '/kandidat/lamaran'] as $url) {
                $browser->visit($url)->assertSourceHas(self::PESAN_403);
            }
        });
    }

    /** DA-20 */
    public function test_pengguna_yang_sudah_login_dialihkan_dari_landing_masuk_dan_registrasi(): void
    {
        $umkm = $this->makeUmkm();

        $this->browse(function (Browser $browser) use ($umkm) {
            $browser->loginAs($umkm);

            foreach (['/', '/login', '/daftar/umkm', '/daftar/kandidat'] as $url) {
                $browser->visit($url)->assertPathIs('/umkm/profil');
            }
        });
    }
}
