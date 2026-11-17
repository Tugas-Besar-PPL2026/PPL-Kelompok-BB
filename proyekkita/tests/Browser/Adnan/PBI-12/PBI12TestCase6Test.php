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

class PBI12TestCase6Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_006_validasi_catatan_wajib_saat_perbaikan_atau_tolak(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Warung Digital',
            'business_field' => 'Kuliner',
            'description' => 'UMKM kuliner.',
            'address' => 'Jl. Riau No. 15',
            'city' => 'Bandung',
            'contact_phone' => '081234567895',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Pemasaran Digital', 'is_active' => true]);

        $proyek = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Proyek Validasi Catatan',
            'description' => 'Deskripsi proyek uji validasi.',
            'expected_output' => 'Hasil pekerjaan.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyek) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek->id]))
                ->pause($jeda)
                ->radio('decision', ModerationDecision::RevisionRequested->value)
                ->clear('note')
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Catatan wajib diisi untuk permintaan perbaikan dan penolakan.');

            $browser->radio('decision', ModerationDecision::Rejected->value)
                ->clear('note')
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Catatan wajib diisi untuk permintaan perbaikan dan penolakan.');
        });

        $this->assertDatabaseHas('projects', [
            'id' => $proyek->id,
            'status' => ProjectStatus::PendingReview->value,
        ]);
    }
}