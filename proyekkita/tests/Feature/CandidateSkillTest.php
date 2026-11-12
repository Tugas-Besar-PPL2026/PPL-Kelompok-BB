<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateSkillTest extends TestCase
{
    use RefreshDatabase;

    private function createKandidatWithProfile(): User
    {
        $user = User::factory()->create(['role' => UserRole::Kandidat]);
        $user->candidateProfile()->create([
            'full_name' => 'Fajar Pratama',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan.',
        ]);

        return $user;
    }

    public function test_kandidat_dapat_membuka_halaman_keterampilan_profil(): void
    {
        $user = $this->createKandidatWithProfile();
        $activeSkill = Skill::create(['name' => 'Figma', 'is_active' => true]);
        $inactiveSkill = Skill::create(['name' => 'Adobe Flash', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('kandidat.profil.edit'));

        $response->assertOk();
        $response->assertSee('Keterampilan');
        $response->assertSee($activeSkill->name);
        $response->assertDontSee($inactiveSkill->name);
    }

    public function test_kandidat_dapat_menambahkan_keterampilan_aktif(): void
    {
        $user = $this->createKandidatWithProfile();
        $skill = Skill::create(['name' => 'UI/UX Design', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('kandidat.profil.keterampilan.store'), [
            'skill_id' => $skill->id,
        ]);

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseHas('candidate_skill', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'skill_id' => $skill->id,
        ]);
    }

    public function test_kandidat_tidak_dapat_menambahkan_keterampilan_nonaktif(): void
    {
        $user = $this->createKandidatWithProfile();
        $inactiveSkill = Skill::create(['name' => 'CorelDraw Lama', 'is_active' => false]);

        $response = $this->actingAs($user)->post(route('kandidat.profil.keterampilan.store'), [
            'skill_id' => $inactiveSkill->id,
        ]);

        $response->assertSessionHasErrors('skill_id');
        $this->assertDatabaseMissing('candidate_skill', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'skill_id' => $inactiveSkill->id,
        ]);
    }

    public function test_kandidat_dapat_menghapus_keterampilan_dari_profil(): void
    {
        $user = $this->createKandidatWithProfile();
        $skill = Skill::create(['name' => 'Canva', 'is_active' => true]);
        $user->candidateProfile->skills()->attach($skill->id);

        $response = $this->actingAs($user)->delete(route('kandidat.profil.keterampilan.destroy', $skill));

        $response->assertRedirect(route('kandidat.profil.edit'));
        $this->assertDatabaseMissing('candidate_skill', [
            'candidate_profile_id' => $user->candidateProfile->id,
            'skill_id' => $skill->id,
        ]);
    }

    public function test_pengguna_non_kandidat_ditolak_mengakses_keterampilan_kandidat(): void
    {
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $skill = Skill::create(['name' => 'Figma', 'is_active' => true]);

        $this->actingAs($umkmUser)
            ->get(route('kandidat.profil.edit'))
            ->assertForbidden();

        $this->actingAs($umkmUser)
            ->post(route('kandidat.profil.keterampilan.store'), ['skill_id' => $skill->id])
            ->assertForbidden();
    }
}
