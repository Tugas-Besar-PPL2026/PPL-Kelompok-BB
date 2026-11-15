<?php

namespace Tests\Feature\Umkm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsUmkmProfileScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/** Halaman proyek UMKM saat profil usaha belum diisi: peringatan + arahkan ke profil, bukan 403. */
class ProfilWajibTest extends TestCase
{
    use BuildsUmkmProfileScenario;
    use RefreshDatabase, UsesInMemoryDatabase;
    use StubsLoginRoute;

    private const PESAN = 'Profil usaha kamu belum lengkap';

    public function test_umkm_tanpa_profil_diarahkan_ke_halaman_profil_dengan_peringatan(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->get(route('umkm.proyek.index'))
            ->assertRedirect(route('umkm.profil.edit'))
            ->assertSessionHas('warning');

        $this->actingAs($user)->followingRedirects()->get(route('umkm.proyek.index'))
            ->assertOk()->assertSee(self::PESAN);
    }

    public function test_form_buat_proyek_juga_diarahkan_dan_post_tanpa_profil_tidak_membuat_proyek(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->get(route('umkm.proyek.create'))->assertRedirect(route('umkm.profil.edit'));
        $this->actingAs($user)->post(route('umkm.proyek.store'), [])->assertRedirect(route('umkm.profil.edit'));
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_umkm_dengan_profil_membuka_daftar_proyek_tanpa_redirect(): void
    {
        $user = $this->makeUmkm($this->profilePayload());

        $this->actingAs($user)->get(route('umkm.proyek.index'))->assertOk();
        // Dibuka berulang: tidak ada pengalihan berulang.
        $this->actingAs($user)->get(route('umkm.proyek.index'))->assertOk();
    }

    public function test_setelah_profil_disimpan_pengguna_kembali_ke_daftar_proyek(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->get(route('umkm.proyek.index'));

        $this->actingAs($user)->put(route('umkm.profil.update'), $this->profilePayload())
            ->assertRedirect(route('umkm.proyek.index'))
            ->assertSessionHas('success');

        $this->actingAs($user)->get(route('umkm.proyek.index'))->assertOk();
    }

    public function test_simpan_profil_tidak_lengkap_tetap_gagal_validasi_dan_tujuan_awal_tetap_diingat(): void
    {
        $user = User::factory()->umkm()->create();
        $this->actingAs($user)->get(route('umkm.proyek.index'));

        $this->actingAs($user)->from(route('umkm.profil.edit'))
            ->put(route('umkm.profil.update'), $this->profilePayload(['business_name' => '']))
            ->assertSessionHasErrors('business_name');

        $this->assertNull($user->umkmProfile()->first());
        $this->actingAs($user)->get(route('umkm.proyek.index'))->assertRedirect(route('umkm.profil.edit'));
    }

    public function test_simpan_profil_tanpa_tujuan_awal_tetap_ke_halaman_profil(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->put(route('umkm.profil.update'), $this->profilePayload())
            ->assertRedirect(route('umkm.profil.edit'));
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->stubLoginRoute();

        $this->get(route('umkm.proyek.index'))->assertRedirect(route('login'));
    }

    public function test_kandidat_dan_admin_tetap_403(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('umkm.proyek.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('umkm.proyek.index'))->assertForbidden();
    }

    public function test_tujuan_awal_dari_post_tidak_disimpan(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->post(route('umkm.proyek.store'), []);

        $this->assertNull(session('umkm_profile_return'));
    }
}
