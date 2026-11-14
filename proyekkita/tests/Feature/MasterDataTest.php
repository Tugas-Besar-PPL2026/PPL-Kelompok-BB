<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_admin_dapat_melihat_halaman_data_master(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Teknologi & Web', 'is_active' => true]);
        $skill = Skill::create(['name' => 'Laravel', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('admin.master.index'));

        $response->assertOk();
        $response->assertSee($category->name);
        $response->assertSee($skill->name);
    }

    public function test_admin_dapat_menambah_keterampilan_baru(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->from(route('admin.master.index'))
            ->post(route('admin.master.skills.store'), [
                'skill_name' => 'Docker',
            ]);

        $response->assertRedirect(route('admin.master.index'));
        $this->assertDatabaseHas('skills', [
            'name' => 'Docker',
            'is_active' => true,
        ]);
    }

    public function test_tambah_keterampilan_gagal_jika_duplikat_beda_kapitalisasi(): void
    {
        $admin = $this->createAdmin();
        Skill::create(['name' => 'Figma', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->from(route('admin.master.index'))
            ->post(route('admin.master.skills.store'), [
                'skill_name' => 'figma',
            ]);

        $response->assertSessionHasErrorsIn('skillCreate', ['skill_name']);
        $this->assertDatabaseCount('skills', 1);
    }

    public function test_admin_dapat_mengubah_nama_dan_status_keterampilan(): void
    {
        $admin = $this->createAdmin();
        $skill = Skill::create(['name' => 'Adobe XD', 'is_active' => true]);

        // Ubah nama
        $this->actingAs($admin)
            ->from(route('admin.master.index'))
            ->put(route('admin.master.skills.update', $skill), ['skill_name' => 'Figma UI'])
            ->assertRedirect(route('admin.master.index'));

        $this->assertDatabaseHas('skills', ['id' => $skill->id, 'name' => 'Figma UI']);

        // Nonaktifkan (soft disable)
        $this->actingAs($admin)
            ->from(route('admin.master.index'))
            ->patch(route('admin.master.skills.toggle', $skill))
            ->assertRedirect(route('admin.master.index'));

        $this->assertDatabaseHas('skills', ['id' => $skill->id, 'is_active' => false]);
    }
}