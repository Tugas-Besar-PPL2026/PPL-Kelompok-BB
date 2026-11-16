<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * Pengujian pengajuan proyek untuk moderasi: syarat pengajuan, perubahan status, dan notifikasi ke admin.
 */
class PengajuanModerasiTest extends TestCase
{
    use RefreshDatabase, UsesInMemoryDatabase, BuildsApplicationScenario;

    private User $umkm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkm = $this->makeUmkm();
    }

    public function test_draf_lengkap_berhasil_diajukan_dan_admin_mendapat_notifikasi(): void
    {
        $admins = User::factory()->admin()->count(2)->create();
        $project = $this->draft();

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertRedirect(route('umkm.proyek.review', $project))
            ->assertSessionHas('success', 'Proyek diajukan dan menunggu moderasi admin.');

        $this->assertSame(ProjectStatus::PendingReview, $project->fresh()->status);
        $this->assertSame(2, Notification::count());

        foreach ($admins as $admin) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'type' => 'proyek',
                'message' => "Proyek baru menunggu moderasi: {$project->title}",
                'related_type' => Project::class,
                'related_id' => $project->id,
                'is_read' => false,
            ]);
        }
    }

    public function test_pernyataan_wajib_dicentang(): void
    {
        $project = $this->draft();

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project))
            ->assertSessionHasErrors(['pernyataan' => 'Centang pernyataan terlebih dahulu.']);

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_umkm_belum_terverifikasi_tidak_bisa_mengajukan(): void
    {
        $umkm = $this->makeUmkm(['verification_status' => VerificationStatus::Pending]);
        $project = $this->draft([], $umkm);

        $this->actingAs($umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertSessionHasErrors('ajukan');

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_total_termin_tidak_sesuai_ditolak(): void
    {
        $project = $this->draft(['total_compensation' => 3000000]);

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertSessionHasErrors([
                'ajukan' => 'Syarat pengajuan belum terpenuhi: Total termin sama dengan total kompensasi.',
            ]);

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_tenggat_lamaran_yang_sudah_lewat_ditolak(): void
    {
        $project = $this->draft(['application_deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertSessionHasErrors('ajukan');

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_proyek_yang_sudah_diajukan_tidak_bisa_diajukan_lagi(): void
    {
        User::factory()->admin()->create();
        $project = $this->draft(['status' => ProjectStatus::PendingReview]);

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertSessionHasErrors(['ajukan' => 'Proyek ini sudah diajukan atau sudah diproses admin.']);

        $this->assertSame(0, Notification::count());
    }

    public function test_umkm_lain_tidak_bisa_mengajukan_proyek_orang_lain(): void
    {
        $project = $this->draft();

        $this->actingAs($this->makeUmkm())
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertForbidden();

        $this->actingAs($this->makeUmkm())
            ->get(route('umkm.proyek.review', $project))
            ->assertForbidden();

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_proyek_perlu_perbaikan_bisa_diajukan_ulang(): void
    {
        $project = $this->draft(['status' => ProjectStatus::RevisionRequested]);

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.submit', $project), ['pernyataan' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ProjectStatus::PendingReview, $project->fresh()->status);
    }

    public function test_tombol_tinjau_menyimpan_draf_lalu_membuka_halaman_tinjau(): void
    {
        $project = $this->draft();

        $this->actingAs($this->umkm)
            ->put(route('umkm.proyek.update', $project), $this->payload(['lanjut_tinjau' => '1']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('umkm.proyek.review', $project));
    }

    public function test_halaman_tinjau_menampilkan_pratinjau_dan_syarat(): void
    {
        $project = $this->draft();

        $this->actingAs($this->umkm)
            ->get(route('umkm.proyek.review', $project))
            ->assertOk()
            ->assertSee('Tinjau &amp; Ajukan Proyek', false)
            ->assertSee($project->title)
            ->assertSee('Syarat Pengajuan')
            ->assertSee('Total termin sama dengan total kompensasi')
            ->assertSee('Ajukan untuk Moderasi');
    }

    /** Draf milik UMKM uji yang memenuhi semua syarat pengajuan. */
    private function draft(array $overrides = [], ?User $umkm = null): Project
    {
        return $this->makeProject(
            array_merge(['status' => ProjectStatus::Draft, 'published_at' => null], $overrides),
            ($umkm ?? $this->umkm)->umkmProfile
        );
    }

    /** Isian form Ubah Draf yang valid; $overrides menimpa isian tertentu. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Redesain Katalog Digital & Label Kemasan',
            'category_id' => Category::firstOrCreate(['name' => 'Desain Grafis & Branding'])->id,
            'skills' => [Skill::firstOrCreate(['name' => 'Figma'])->id],
            'description' => 'Memperbarui label kemasan tiga varian biji kopi.',
            'expected_output' => 'Desain label kemasan 3 SKU siap cetak',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'target_completion_date' => now()->addDays(30)->toDateString(),
            'quota' => 2,
            'total_compensation' => 2500000,
            'payment_method' => 'bertahap',
            'terms' => [
                ['amount' => 1250000, 'condition' => 'Uang muka setelah kesepakatan berstatus aktif'],
                ['amount' => 1250000, 'condition' => 'Pelunasan setelah hasil akhir disetujui UMKM'],
            ],
        ], $overrides);
    }
}
