<?php

namespace Tests\Browser\Adnan\PBI12;

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

class PBI12TestCase2Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_002_menyetujui_proyek_dengan_dan_tanpa_catatan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Batik Nusantara',
            'business_field' => 'Fashion & Kerajinan',
            'description' => 'UMKM pengrajin batik.',
            'address' => 'Jl. Braga No. 12',
            'city' => 'Bandung',
            'contact_phone' => '081234567891',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Riset & Analisis', 'is_active' => true]);

        $proyek1 = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Rekap Data Penjualan Q3 ke Excel',
            'description' => 'Rekap laporan penjualan kuartal 3.',
            'expected_output' => 'File Excel rapi.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $proyek2 = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Riset Pasar Ekspor Batik Tulis',
            'description' => 'Riset potensi pasar luar negeri.',
            'expected_output' => 'Dokumen laporan riset PDF.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(21)->toDateString(),
            'quota' => 2,
            'total_compensation' => 2000000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyek1, $proyek2) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek1->id]))
                ->pause($jeda)
                ->assertSee($proyek1->title)
                ->radio('decision', ModerationDecision::Approved->value)
                ->pause($jeda)
                ->type('note', 'Informasi proyek lengkap.')
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Keputusan moderasi proyek berhasil disimpan.');

            $browser->visit(route('admin.moderasi.index', ['project' => $proyek2->id]))
                ->pause($jeda)
                ->assertSee($proyek2->title)
                ->radio('decision', ModerationDecision::Approved->value)
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Keputusan moderasi proyek berhasil disimpan.');
        });

        $this->assertDatabaseHas('projects', [
            'id' => $proyek1->id,
            'status' => ProjectStatus::Published->value,
        ]);
        $this->assertNotNull($proyek1->fresh()->published_at);

        $this->assertDatabaseHas('projects', [
            'id' => $proyek2->id,
            'status' => ProjectStatus::Published->value,
        ]);
        $this->assertDatabaseHas('project_moderations', [
            'project_id' => $proyek2->id,
            'decision' => ModerationDecision::Approved->value,
            'note' => null,
        ]);
    }
}