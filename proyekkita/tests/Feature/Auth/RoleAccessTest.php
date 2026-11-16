<?php

namespace Tests\Feature\Auth;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * [FR-01] Proteksi halaman: tamu diarahkan ke Masuk, dan setiap peran hanya dapat membuka halaman perannya (403 bila salah).
 * Mencakup Data Master dan Moderasi Proyek (admin) serta pembuatan proyek (UMKM).
 */
class RoleAccessTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    /** Halaman (GET) per peran. */
    private function halaman(): array
    {
        return [
            'admin' => ['admin.master.index', 'admin.moderasi.index', 'admin.verifikasi.index'],
            'umkm' => ['umkm.profil.edit', 'umkm.proyek.index', 'umkm.proyek.create', 'umkm.seleksi.index'],
            'kandidat' => ['kandidat.profil.edit', 'kandidat.profil.datadiri', 'kandidat.proyek.index', 'kandidat.lamaran.index'],
        ];
    }

    /** Aksi tulis (POST) per peran; sengaja yang tidak memakai route model binding. */
    private function aksiTulis(): array
    {
        return [
            'admin' => ['admin.master.categories.store', 'admin.master.skills.store'],
            'umkm' => ['umkm.proyek.store'],
            'kandidat' => ['kandidat.profil.keterampilan.store', 'kandidat.profil.portofolio.store'],
        ];
    }

    public function test_tamu_diarahkan_ke_halaman_masuk_untuk_semua_halaman_privat(): void
    {
        foreach ($this->halaman() as $rute) {
            foreach ($rute as $nama) {
                $this->get(route($nama))->assertRedirect(route('login'));
            }
        }

        foreach ($this->aksiTulis() as $rute) {
            foreach ($rute as $nama) {
                $this->post(route($nama))->assertRedirect(route('login'));
            }
        }
    }

    public function test_setiap_peran_ditolak_403_pada_halaman_peran_lain(): void
    {
        foreach (['admin', 'umkm', 'kandidat'] as $peran) {
            $user = User::factory()->{$peran}()->create();

            foreach ($this->halaman() as $pemilik => $rute) {
                if ($pemilik === $peran) {
                    continue;
                }
                foreach ($rute as $nama) {
                    $this->actingAs($user)->get(route($nama))->assertForbidden();
                }
            }

            foreach ($this->aksiTulis() as $pemilik => $rute) {
                if ($pemilik === $peran) {
                    continue;
                }
                foreach ($rute as $nama) {
                    $this->actingAs($user)->post(route($nama))->assertForbidden();
                }
            }
        }
    }

    public function test_kandidat_dan_umkm_tidak_dapat_membuka_data_master_dan_moderasi_proyek(): void
    {
        foreach (['umkm', 'kandidat'] as $peran) {
            $user = User::factory()->{$peran}()->create();

            $this->actingAs($user)->get(route('admin.master.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.moderasi.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.master.categories.store'), ['name' => 'Peretasan'])->assertForbidden();
        }

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_dan_kandidat_tidak_dapat_membuat_proyek(): void
    {
        foreach (['admin', 'kandidat'] as $peran) {
            $user = User::factory()->{$peran}()->create();

            $this->actingAs($user)->get(route('umkm.proyek.create'))->assertForbidden();
            $this->actingAs($user)->post(route('umkm.proyek.store'), [])->assertForbidden();
        }

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_setiap_peran_dapat_membuka_halaman_perannya(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.master.index'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.verifikasi.index'))->assertOk();

        $this->actingAs(User::factory()->kandidat()->create())
            ->get(route('kandidat.profil.edit'))->assertOk();
    }

    /** FR-01: UMKM belum terverifikasi tetap dapat mengelola profil dan membuat draf proyek. */
    public function test_umkm_pending_dapat_membuka_profil_usaha_dan_formulir_proyek(): void
    {
        $umkm = $this->makeUmkm(['verification_status' => VerificationStatus::Pending]);

        $this->actingAs($umkm)->get(route('umkm.profil.edit'))->assertOk();
        $this->actingAs($umkm)->get(route('umkm.proyek.create'))->assertOk();
    }

    public function test_akun_baru_tanpa_profil_dapat_membuka_halaman_awal_perannya(): void
    {
        $this->actingAs(User::factory()->umkm()->create())->get(route('umkm.profil.edit'))->assertOk();
        $this->actingAs(User::factory()->kandidat()->create())->get(route('kandidat.profil.edit'))->assertOk();
    }
}
