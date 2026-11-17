<?php

namespace Tests\Feature\Kandidat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * Integrasi data registrasi ke halaman profil.
 * Pemetaan: users.name -> candidate_profiles.full_name (prefill, tanpa membuat baris profil saat registrasi).
 * UMKM: tidak ada kolom umkm_profiles yang sepadan dengan isian registrasi, sehingga perilakunya tidak berubah.
 */
class PrefillDataRegistrasiTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    private function daftar(string $role, string $name, string $email)
    {
        return $this->post(route('register.store', ['role' => $role]), [
            'name' => $name,
            'email' => $email,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'terms' => '1',
        ]);
    }

    private function dataDiri(array $o = []): array
    {
        return array_merge([
            'full_name' => 'Fajar Pratama',
            'city' => 'Kota Bandung',
            'headline' => 'Desainer grafis',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'summary' => 'Mahasiswa DKV yang fokus pada desain kemasan.',
        ], $o);
    }

    public function test_nama_registrasi_kandidat_terisi_otomatis_di_data_diri(): void
    {
        $this->daftar('kandidat', 'Fajar Pratama', 'fajar.prefill@proyekkita.test');

        $this->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('value="Fajar Pratama"', false)
            ->assertSee('id="completeness-pct">0%', false);

        // Prefill tidak menulis apa pun: profil baru dibuat saat pengguna menyimpan Data Diri.
        $this->assertDatabaseCount('candidate_profiles', 0);
    }

    public function test_simpan_data_diri_membuat_profil_yang_terhubung_ke_akun(): void
    {
        $this->daftar('kandidat', 'Fajar Pratama', 'fajar.simpan@proyekkita.test');
        $user = User::where('email', 'fajar.simpan@proyekkita.test')->firstOrFail();

        $this->put(route('kandidat.profil.datadiri.update'), $this->dataDiri())
            ->assertRedirect(route('kandidat.profil.datadiri'));

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Fajar Pratama']);
        $this->assertDatabaseCount('candidate_profiles', 1);
    }

    public function test_data_tetap_tersedia_setelah_logout_dan_login_kembali(): void
    {
        $this->daftar('kandidat', 'Fajar Pratama', 'fajar.ulang@proyekkita.test');
        $this->put(route('kandidat.profil.datadiri.update'), $this->dataDiri(['city' => 'Kota Cimahi']));

        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.attempt'), ['email' => 'fajar.ulang@proyekkita.test', 'password' => 'rahasia123']);

        $this->get(route('kandidat.profil.datadiri'))
            ->assertOk()
            ->assertSee('value="Fajar Pratama"', false)
            ->assertSee('value="Kota Cimahi"', false);
    }

    public function test_nama_lengkap_tersimpan_tidak_ditimpa_nama_akun(): void
    {
        $user = $this->makeKandidat(['full_name' => 'Nama Profil Tersimpan']);
        $user->forceFill(['name' => 'Nama Akun Berbeda'])->save();

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertSee('value="Nama Profil Tersimpan"', false)
            ->assertDontSee('value="Nama Akun Berbeda"', false);

        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Nama Profil Tersimpan']);
    }

    public function test_perubahan_nama_lengkap_tersimpan_ke_profil(): void
    {
        $user = $this->makeKandidat(['full_name' => 'Nama Lama']);

        $this->actingAs($user)->put(route('kandidat.profil.datadiri.update'), $this->dataDiri(['full_name' => 'Nama Baru']));

        $this->actingAs($user->fresh())->get(route('kandidat.profil.datadiri'))
            ->assertSee('value="Nama Baru"', false);
        $this->assertDatabaseHas('candidate_profiles', ['user_id' => $user->id, 'full_name' => 'Nama Baru']);
    }

    public function test_input_lama_setelah_validasi_gagal_tetap_diutamakan(): void
    {
        $this->daftar('kandidat', 'Fajar Pratama', 'fajar.old@proyekkita.test');

        $this->from(route('kandidat.profil.datadiri'))
            ->put(route('kandidat.profil.datadiri.update'), $this->dataDiri(['full_name' => 'Diketik Pengguna', 'summary' => '']))
            ->assertSessionHasErrors('summary');

        $this->get(route('kandidat.profil.datadiri'))
            ->assertSee('value="Diketik Pengguna"', false);
    }

    public function test_registrasi_umkm_tetap_tanpa_profil_dan_halaman_profil_dapat_dibuka(): void
    {
        $this->daftar('umkm', 'Rina Kartika', 'rina.prefill@proyekkita.test');

        $this->get(route('umkm.profil.edit'))->assertOk();
        $this->assertDatabaseCount('umkm_profiles', 0);
    }

    public function test_akun_umkm_lama_dengan_profil_tetap_menampilkan_data(): void
    {
        $user = $this->makeUmkm(['business_name' => 'Kopi Lama']);

        $this->actingAs($user)->get(route('umkm.profil.edit'))
            ->assertOk()
            ->assertSee('value="Kopi Lama"', false);
    }
}
