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

class PBI12TestCase7Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_007_batas_panjang_catatan_1000_karakter(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Kriya Bambu',
            'business_field' => 'Kerajinan',
            'description' => 'UMKM kerajinan bambu.',
            'address' => 'Jl. Cihampelas No. 30',
            'city' => 'Bandung',
            'contact_phone' => '081234567896',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Desain & Kreatif', 'is_active' => true]);

        $proyek = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Proyek Batas 1000 Karakter',
            'description' => 'Deskripsi proyek batas karakter.',
            'expected_output' => 'Hasil pekerjaan.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $catatan1000 = str_repeat('A', 1000);

        $this->browse(function (Browser $browser) use ($admin, $proyek, $catatan1000) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek->id]))
                ->pause($jeda)
                ->radio('decision', ModerationDecision::RevisionRequested->value)
                ->type('note', $catatan1000)
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Keputusan moderasi proyek berhasil disimpan.');
        });

        $this->assertDatabaseHas('projects', [
            'id' => $proyek->id,
            'status' => ProjectStatus::RevisionRequested->value,
        ]);
    }
}