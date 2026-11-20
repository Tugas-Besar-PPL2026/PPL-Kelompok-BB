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

class PBI12TestCase5Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_005_menolak_proyek_dengan_catatan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $kandidat = User::factory()->create(['role' => UserRole::Kandidat]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'CV Global Tech',
            'business_field' => 'Teknologi',
            'description' => 'UMKM teknologi informasi.',
            'address' => 'Jl. Soekarno Hatta No. 100',
            'city' => 'Bandung',
            'contact_phone' => '081234567894',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Pengembangan Web', 'is_active' => true]);

        $proyek = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Pembuatan Sistem ERP Multinasional',
            'description' => 'Membangun ERP skala besar.',
            'expected_output' => 'Sistem ERP penuh.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(30)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyek, $kandidat) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek->id]))
                ->pause($jeda)
                ->assertSee($proyek->title)
                ->radio('decision', ModerationDecision::Rejected->value)
                ->pause($jeda)
                ->type('note', 'Ruang lingkup melebihi proyek jangka pendek.')
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Keputusan moderasi proyek berhasil disimpan.');

            $browser->loginAs($kandidat)
                ->visit('/kandidat/proyek')
                ->pause($jeda)
                ->assertDontSee($proyek->title);
        });

        $this->assertDatabaseHas('projects', [
            'id' => $proyek->id,
            'status' => ProjectStatus::Rejected->value,
        ]);
        $this->assertDatabaseHas('project_moderations', [
            'project_id' => $proyek->id,
            'decision' => ModerationDecision::Rejected->value,
            'note' => 'Ruang lingkup melebihi proyek jangka pendek.',
        ]);
    }
}
