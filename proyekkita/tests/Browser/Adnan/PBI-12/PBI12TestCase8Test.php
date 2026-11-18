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

class PBI12TestCase8Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_008_pengajuan_ulang_dan_penambahan_riwayat_baru(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Gudang Sembako Jaya',
            'business_field' => 'Perdagangan',
            'description' => 'UMKM distribusi sembako.',
            'address' => 'Jl. Sudirman No. 40',
            'city' => 'Bandung',
            'contact_phone' => '081234567897',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Administrasi & Data', 'is_active' => true]);

        $proyek = Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => 'Rekap Stok Gudang Bulanan',
            'description' => 'Rekap stok gudang setelah revisi.',
            'expected_output' => 'Laporan stok bulanan lengkap.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(14)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1250000,
            'status' => ProjectStatus::PendingReview,
        ]);

        $proyek->moderations()->create([
            'admin_id' => $admin->id,
            'decision' => ModerationDecision::RevisionRequested,
            'note' => 'Mohon perbaiki rincian deliverable awal.',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proyek) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index', ['project' => $proyek->id]))
                ->pause($jeda)
                ->assertSee('Pengajuan ulang')
                ->assertSee('Diminta Perbaikan oleh Admin')
                ->assertSee('Mohon perbaiki rincian deliverable awal.')
                ->radio('decision', ModerationDecision::Approved->value)
                ->type('note', 'Revisi sudah sesuai.')
                ->pause($jeda)
                ->press('Simpan Keputusan')
                ->pause($jeda)
                ->assertSee('Keputusan moderasi proyek berhasil disimpan.');
        });

        $this->assertDatabaseHas('projects', [
            'id' => $proyek->id,
            'status' => ProjectStatus::Published->value,
        ]);
        $this->assertDatabaseCount('project_moderations', 2);
    }
}