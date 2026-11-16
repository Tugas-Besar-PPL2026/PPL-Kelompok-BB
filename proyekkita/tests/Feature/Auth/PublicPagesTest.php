<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/** [FR-01] Halaman publik (Landing, Masuk, Registrasi) dapat diakses tanpa login. */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase, UsesInMemoryDatabase;

    public function test_landing_page_dapat_diakses_tanpa_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cara kerja ProyekKita')
            ->assertSee('Untuk UMKM')
            ->assertSee('Mendukung SDG 8')
            ->assertSee('UMKM diverifikasi')
            ->assertSee('Proyek dimoderasi')
            ->assertSee('Pembayaran tercatat')
            ->assertSee(route('login'), false)
            ->assertSee(route('register', ['role' => 'umkm']), false)
            ->assertSee(route('register', ['role' => 'kandidat']), false);
    }

    public function test_landing_menampilkan_lima_tahap_cara_kerja(): void
    {
        $this->get('/')
            ->assertSee('Ajukan proyek')
            ->assertSee('Cari &amp; lamar', false)
            ->assertSee('Seleksi &amp; kesepakatan', false)
            ->assertSee('Pengerjaan &amp; revisi', false)
            ->assertSee('Pembayaran &amp; evaluasi', false);
    }

    public function test_halaman_masuk_dan_registrasi_dapat_diakses_tanpa_login(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register', ['role' => 'umkm']))->assertOk();
        $this->get(route('register', ['role' => 'kandidat']))->assertOk();
    }

    public function test_route_halaman_publik_dan_auth_terdaftar_dengan_nama_yang_diharapkan(): void
    {
        foreach (['landing', 'login', 'login.attempt', 'register', 'register.store', 'logout'] as $nama) {
            $this->assertTrue(app('router')->has($nama), "Route {$nama} belum terdaftar.");
        }
    }
}
