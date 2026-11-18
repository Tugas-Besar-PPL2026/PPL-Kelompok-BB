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

class PBI12TestCase4Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_004_minta_perbaikan_dengan_catatan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $kandidat = User::factory()->create(['role' => UserRole::Kandidat]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Studio Kreatif',
            'business_field' => 'Desain',
            'description' => 'UMKM jasa kreatif.',
            'address' => 'Jl. Dago No. 20',
            'city' => 'Bandung',
            'contact_phone' => '081234567893',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Desain & Kreatif', 'is_active' => true]);

        $proyek = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Desain Feed Instagram 9 Konten',
            'description' => 'Membuat 9 desain feed IG.',
            'expected_output' => 'File PNG dan Canva.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 750000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyek, $kandidat) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek->id]))
                ->pause($jeda)
                ->assertSee($proyek->title)
                ->radio('decision', ModerationDecision::RevisionRequested->value)
                ->pause($jeda)
                ->type('note', 'Lengkapi hasil pekerjaan dan sesuaikan kompensasi.')
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
            'status' => ProjectStatus::RevisionRequested->value,
        ]);
        $this->assertDatabaseHas('project_moderations', [
            'project_id' => $proyek->id,
            'decision' => ModerationDecision::RevisionRequested->value,
            'note' => 'Lengkapi hasil pekerjaan dan sesuaikan kompensasi.',
        ]);
    }
}
