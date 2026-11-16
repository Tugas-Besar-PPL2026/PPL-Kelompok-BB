<?php

namespace Tests\Browser;

use App\Enums\ModerationDecision;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProjectModerationTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createPendingProject(string $title = 'Redesign Kemasan Kopi'): Project
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
            'title' => $title,
            'description' => 'Membuat desain kemasan pouch 250gr.',
            'expected_output' => 'File siap cetak AI dan PDF.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(21)->toDateString(),
            'quota' => 2,
            'total_compensation' => 1500000,
            'status' => ProjectStatus::PendingReview,
        ]);
    }

    public function test_admin_dapat_melihat_antrean_dan_detail_proyek_di_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject('Redesign Kemasan Kopi');

        $this->browse(function (Browser $browser) use ($admin, $project) {
            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->assertSee($project->title)
                ->assertSee('Kopi Nusantara')
                ->assertSee('Membuat desain kemasan pouch 250gr.');
        });
    }

    public function test_admin_dapat_menyetujui_proyek_lewat_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject('Pembuatan Website Katalog');

        $this->browse(function (Browser $browser) use ($admin, $project) {
            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->assertSee($project->title)
                ->radio('decision', ModerationDecision::Approved->value)
                ->type('note', 'Dokumen dan rincian proyek lengkap.')
                ->press('Simpan Keputusan')
                ->pause(500);
        });

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Published->value,
        ]);
    }

    public function test_admin_dapat_meminta_perbaikan_proyek_lewat_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject('Desain Banner Promo');

        $this->browse(function (Browser $browser) use ($admin, $project) {
            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->assertSee($project->title)
                ->radio('decision', ModerationDecision::RevisionRequested->value)
                ->type('note', 'Mohon perjelas spesifikasi ukuran banner.')
                ->press('Simpan Keputusan')
                ->pause(500);
        });

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::RevisionRequested->value,
        ]);
    }

    public function test_admin_dapat_menolak_proyek_dengan_catatan_lewat_browser(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $project = $this->createPendingProject('Proyek Tanpa Rincian Jelas');

        $this->browse(function (Browser $browser) use ($admin, $project) {
            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->assertSee($project->title)
                ->radio('decision', ModerationDecision::Rejected->value)
                ->type('note', 'Proyek tidak sesuai ketentuan platform.')
                ->press('Simpan Keputusan')
                ->pause(500);
        });

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Rejected->value,
        ]);
    }
}