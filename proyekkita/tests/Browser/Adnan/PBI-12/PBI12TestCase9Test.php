<?php

namespace Tests\Browser\Adnan\PBI12;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PBI12TestCase9Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_009_proyek_non_menunggu_tidak_menampilkan_form_keputusan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Toko Roti Harum',
            'business_field' => 'Kuliner',
            'description' => 'UMKM roti dan kue.',
            'address' => 'Jl. Setiabudi No. 50',
            'city' => 'Bandung',
            'contact_phone' => '081234567898',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Pemasaran Digital', 'is_active' => true]);

        $proyekPublished = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Proyek Sudah Dipublikasikan',
            'description' => 'Deskripsi proyek published.',
            'expected_output' => 'Hasil pekerjaan.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::Published,
            'published_at' => now(),
        ]);

        $proyekDraft = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Proyek Masih Draf',
            'description' => 'Deskripsi proyek draft.',
            'expected_output' => 'Hasil pekerjaan.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::Draft,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyekPublished, $proyekDraft) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['status' => 'published', 'project' => $proyekPublished->id]))
                ->pause($jeda)
                ->assertSee($proyekPublished->title)
                ->assertDontSee('Simpan Keputusan');

            $browser->visit(route('admin.moderasi.index'))
                ->pause($jeda)
                ->assertDontSee($proyekDraft->title);
        });
    }
}
