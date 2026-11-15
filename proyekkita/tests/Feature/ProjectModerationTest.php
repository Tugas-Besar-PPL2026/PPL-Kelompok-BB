<?php

namespace Tests\Feature;

use App\Enums\ModerationDecision;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModerationTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingProject(): Project
    {
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'UMKM pengolah biji kopi nusantara.',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'contact_phone' => '081234567890',
            'verification_status' => 'verified',
        ]);
        $category = Category::create(['name' => 'Desain & Kreatif', 'is_active' => true]);

        return Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $category->id,
            'title' => 'Redesign Kemasan Kopi',
            'description' => 'Membuat desain kemasan pouch 250gr.',
            'expected_output' => 'File siap cetak AI dan PDF.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(21)->toDateString(),
            'quota' => 2,
            'total_compensation' => 1500000,
            'status' => ProjectStatus::PendingReview,
        ]);
    }

    public function test_admin_dapat_menyetujui_dan_mempublikasikan_proyek(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject();

        $this->actingAs($admin)
            ->post(route('admin.moderasi.decide', $project), [
                'decision' => ModerationDecision::Approved->value,
                'note' => 'Dokumen dan rincian proyek lengkap.',
            ])
            ->assertRedirect(route('admin.moderasi.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Published->value,
        ]);
        $this->assertNotNull($project->fresh()->published_at);
        $this->assertDatabaseHas('project_moderations', [
            'project_id' => $project->id,
            'admin_id' => $admin->id,
            'decision' => ModerationDecision::Approved->value,
        ]);
    }

    public function test_admin_dapat_meminta_perbaikan_dengan_catatan_wajib(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject();

        // Gagal jika tanpa catatan
        $this->actingAs($admin)
            ->post(route('admin.moderasi.decide', $project), [
                'decision' => ModerationDecision::RevisionRequested->value,
                'note' => '',
            ])
            ->assertSessionHasErrors('note');

        // Berhasil jika dengan catatan
        $this->actingAs($admin)
            ->post(route('admin.moderasi.decide', $project), [
                'decision' => ModerationDecision::RevisionRequested->value,
                'note' => 'Mohon perjelas spesifikasi ukuran kemasan.',
            ])
            ->assertRedirect(route('admin.moderasi.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::RevisionRequested->value,
        ]);
    }

    public function test_admin_dapat_menolak_proyek_dengan_catatan_wajib(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject();

        $this->actingAs($admin)
            ->post(route('admin.moderasi.decide', $project), [
                'decision' => ModerationDecision::Rejected->value,
                'note' => 'Proyek tidak sesuai ketentuan platform.',
            ])
            ->assertRedirect(route('admin.moderasi.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Rejected->value,
        ]);
    }
}