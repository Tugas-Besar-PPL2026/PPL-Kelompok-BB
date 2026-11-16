<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function createKandidatWithProfile(string $name = 'Fajar Pratama'): User
    {
        $user = User::factory()->create(['role' => UserRole::Kandidat]);
        $user->candidateProfile()->create([
            'full_name' => $name,
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan.',
        ]);

        return $user;
    }

    public function test_kandidat_dapat_melihat_daftar_pengalaman_kerja(): void
    {
        $user = $this->createKandidatWithProfile();
        $experience = $user->candidateProfile->experiences()->create([
            'work_type' => 'Desain kemasan produk UMKM (freelance)',
            'duration' => '8 bulan',
            'description' => 'Merancang label dan kemasan untuk 4 UMKM makanan ringan di Bandung.',
        ]);

        $response = $this->actingAs($user)->get(route('kandidat.profil.edit'));

        $response->assertOk();
        $response->assertSee('Pengalaman Kerja');
        $response->assertSee($experience->work_type);
        $response->assertSee($experience->duration);
        $response->assertSee($experience->description);
    }

    public function test_kandidat_dapat_menambahkan_pengalaman_kerja_baru(): void
    {
        $user = $this->createKandidatWithProfile();

        $response = $this->actingAs($user)->post(route('kandidat.profil.pengalaman.store'), [
            'work_type' => 'Desainer grafis Himpunan Mahasiswa DKV',
            'duration' => '6 bulan',
            'description' => 'Membuat materi publikasi acara dan konten Instagram himpunan.',
        ]);

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseHas('experiences', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'work_type' => 'Desainer grafis Himpunan Mahasiswa DKV',
            'duration' => '6 bulan',
            'description' => 'Membuat materi publikasi acara dan konten Instagram himpunan.',
        ]);
    }

    public function test_kandidat_dapat_menambahkan_pengalaman_kerja_tanpa_deskripsi(): void
    {
        $user = $this->createKandidatWithProfile();

        $response = $this->actingAs($user)->post(route('kandidat.profil.pengalaman.store'), [
            'work_type' => 'Admin media sosial toko online',
            'duration' => '3 bulan',
            'description' => '',
        ]);

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseHas('experiences', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'work_type' => 'Admin media sosial toko online',
            'duration' => '3 bulan',
            'description' => '',
        ]);
    }

    public function test_validasi_wajib_isi_bidang_pekerjaan_dan_durasi(): void
    {
        $user = $this->createKandidatWithProfile();

        $response = $this->actingAs($user)->post(route('kandidat.profil.pengalaman.store'), [
            'work_type' => '',
            'duration' => '',
        ]);

        $response->assertSessionHasErrors(['work_type', 'duration']);
    }

    public function test_kandidat_dapat_memperbarui_pengalaman_kerja(): void
    {
        $user = $this->createKandidatWithProfile();
        $experience = $user->candidateProfile->experiences()->create([
            'work_type' => 'Desain grafis',
            'duration' => '4 bulan',
            'description' => 'Deskripsi awal.',
        ]);

        $response = $this->actingAs($user)->put(route('kandidat.profil.pengalaman.update', $experience), [
            'work_type' => 'Desain kemasan produk UMKM (freelance)',
            'duration' => '8 bulan',
            'description' => 'Deskripsi diperbarui.',
        ]);

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseHas('experiences', [
            'id' => $experience->id,
            'work_type' => 'Desain kemasan produk UMKM (freelance)',
            'duration' => '8 bulan',
            'description' => 'Deskripsi diperbarui.',
        ]);
    }

    public function test_kandidat_dapat_menghapus_pengalaman_kerja(): void
    {
        $user = $this->createKandidatWithProfile();
        $experience = $user->candidateProfile->experiences()->create([
            'work_type' => 'Desain kemasan produk UMKM (freelance)',
            'duration' => '8 bulan',
            'description' => 'Deskripsi pekerjaan.',
        ]);

        $response = $this->actingAs($user)->delete(route('kandidat.profil.pengalaman.destroy', $experience));

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseMissing('experiences', [
            'id' => $experience->id,
        ]);
    }

    public function test_kandidat_tidak_dapat_mengubah_atau_menghapus_pengalaman_kandidat_lain(): void
    {
        $owner = $this->createKandidatWithProfile('Pemilik');
        $other = $this->createKandidatWithProfile('Kandidat Lain');

        $experience = $owner->candidateProfile->experiences()->create([
            'work_type' => 'Desain kemasan',
            'duration' => '5 bulan',
            'description' => 'Milik kandidat pertama.',
        ]);

        $this->actingAs($other)
            ->put(route('kandidat.profil.pengalaman.update', $experience), [
                'work_type' => 'Diubah',
                'duration' => '1 bulan',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('kandidat.profil.pengalaman.destroy', $experience))
            ->assertForbidden();
    }
}
