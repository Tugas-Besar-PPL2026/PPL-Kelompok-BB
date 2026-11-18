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

class PBI12TestCase3Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_003_proyek_disetujui_tampil_ke_kandidat(): void
    {
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Sumber Makmur',
            'business_field' => 'Perdagangan',
            'description' => 'UMKM perdagangan umum.',
            'address' => 'Jl. Asia Afrika No. 5',
            'city' => 'Bandung',
            'contact_phone' => '081234567892',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Administrasi & Data', 'is_active' => true]);

        Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Rekap Data Penjualan Q3 ke Excel',
            'description' => 'Rekap penjualan Q3.',
            'expected_output' => 'File Excel.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 2,
            'total_compensation' => 1200000,
            'status' => ProjectStatus::Published,
            'published_at' => now(),
        ]);

        $kandidat = User::factory()->create(['role' => UserRole::Kandidat]);

        $this->browse(function (Browser $browser) use ($kandidat) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($kandidat)
                ->visit('/kandidat/proyek')
                ->pause($jeda)
                ->assertSee('Rekap Data Penjualan Q3 ke Excel');
        });
    }
}
