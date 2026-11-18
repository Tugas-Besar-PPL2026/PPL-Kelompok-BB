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

class PBI12TestCase1Test extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tc_pbi12_001_tampilkan_antrean_dan_navigasi_moderasi(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $umkmUser = User::factory()->create(['role' => UserRole::Umkm]);
        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Kopi Nusantara',
            'business_field' => 'Kuliner & Minuman',
            'description' => 'UMKM pengolah kopi lokal.',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'contact_phone' => '081234567890',
            'verification_status' => 'verified',
        ]);
        $kat = Category::create(['name' => 'Administrasi & Data', 'is_active' => true]);

        $buatProyek = fn (string $judul, ProjectStatus $status) => Project::create([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $kat->id,
            'title' => $judul,
            'description' => 'Deskripsi pekerjaan ' . $judul,
            'expected_output' => 'Hasil pekerjaan sesuai target.',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(21)->toDateString(),
            'quota' => 2,
            'total_compensation' => 1500000,
            'status' => $status,
            'published_at' => $status === ProjectStatus::Published ? now() : null,
        ]);

        // 4 Menunggu, 2 Disetujui, 1 Perlu Perbaikan, 1 Ditolak
        $buatProyek('Rekap Stok Gudang Bulanan', ProjectStatus::PendingReview);
        $buatProyek('Desain Kemasan Kopi', ProjectStatus::PendingReview);
        $buatProyek('Foto Produk Katalog', ProjectStatus::PendingReview);
        $buatProyek('Admin Media Sosial', ProjectStatus::PendingReview);

        $disetujui = $buatProyek('Website Profil Usaha', ProjectStatus::Published);
        $buatProyek('Video Promosi Singkat', ProjectStatus::Published);
        $buatProyek('Landing Page Promo', ProjectStatus::RevisionRequested);
        $buatProyek('Proyek Tidak Sesuai', ProjectStatus::Rejected);

        $this->browse(function (Browser $browser) use ($admin, $disetujui) {
            $jeda = (int) env('DUSK_JEDA', 1000);

            $browser->loginAs($admin)
                ->visit(route('admin.moderasi.index'))
                ->pause($jeda)
                ->assertSee('Moderasi Proyek')
                ->assertSee('Rekap Stok Gudang Bulanan')
                ->assertSee('Menunggu')
                ->assertSee('4')
                ->assertSee('Disetujui')
                ->assertSee('2')
                ->assertSee('Perlu Perbaikan')
                ->assertSee('1')
                ->assertSee('Ditolak')
                ->clickLink('Disetujui')
                ->pause($jeda)
                ->assertSee($disetujui->title)
                ->clickLink($disetujui->title)
                ->pause($jeda)
                ->assertSee('Riwayat Moderasi Proyek Ini');
        });
    }
}