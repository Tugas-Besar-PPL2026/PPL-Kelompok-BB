<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * PBI-9 - Membuat dan memperbarui draf proyek: penyimpanan draf, validasi isian, dan hak akses Ubah Draf.
 */
class DrafProyekTest extends TestCase
{
    use RefreshDatabase, UsesInMemoryDatabase, BuildsApplicationScenario;

    private User $umkm;

    private Category $category;

    private Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkm = $this->makeUmkm();
        $this->category = Category::firstOrCreate(['name' => 'Desain Grafis & Branding']);
        $this->skill = Skill::firstOrCreate(['name' => 'Figma']);
    }

    public function test_halaman_buat_proyek_hanya_menampilkan_kategori_aktif(): void
    {
        Category::create(['name' => 'Kategori Lama', 'is_active' => false]);

        $this->actingAs($this->umkm)
            ->get(route('umkm.proyek.create'))
            ->assertOk()
            ->assertSee('Buat Proyek Baru')
            ->assertSee('Desain Grafis &amp; Branding', false)
            ->assertDontSee('Kategori Lama');
    }

    public function test_umkm_menyimpan_draf_proyek_baru(): void
    {
        $response = $this->actingAs($this->umkm)->post(route('umkm.proyek.store'), $this->payload());

        $project = Project::firstOrFail();

        $response->assertRedirect(route('umkm.proyek.edit', $project))
            ->assertSessionHas('success', 'Draf proyek disimpan.');

        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame($this->umkm->umkmProfile->id, $project->umkm_profile_id);
        $this->assertSame('Redesain Katalog Digital & Label Kemasan', $project->title);
        $this->assertSame(2, $project->quota);
        $this->assertEquals([$this->skill->id], $project->skills()->pluck('skills.id')->all());
    }

    public function test_kolom_wajib_kosong_ditolak(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), [])
            ->assertSessionHasErrors([
                'title' => 'Judul proyek wajib diisi.',
                'category_id' => 'Kategori wajib diisi.',
                'skills' => 'Keterampilan wajib diisi.',
                'description' => 'Deskripsi pekerjaan wajib diisi.',
                'expected_output' => 'Hasil yang diharapkan wajib diisi.',
                'application_deadline' => 'Tenggat lamaran wajib diisi.',
                'target_completion_date' => 'Target penyelesaian wajib diisi.',
                'quota' => 'Kuota kandidat wajib diisi.',
                'total_compensation' => 'Total kompensasi wajib diisi.',
            ]);

        $this->assertSame(0, Project::count());
    }

    public function test_tenggat_dan_target_penyelesaian_harus_berurutan(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload([
                'application_deadline' => now()->toDateString(),
                'target_completion_date' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors([
                'application_deadline' => 'Tenggat lamaran paling cepat besok.',
                'target_completion_date' => 'Target penyelesaian harus setelah tenggat lamaran.',
            ]);

        $this->assertSame(0, Project::count());
    }

    public function test_kategori_nonaktif_kuota_dan_kompensasi_di_luar_batas_ditolak(): void
    {
        $nonaktif = Category::create(['name' => 'Kategori Lama', 'is_active' => false]);

        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload([
                'category_id' => $nonaktif->id,
                'quota' => 1001,
                'total_compensation' => 10000000000,
            ]))
            ->assertSessionHasErrors([
                'category_id' => 'Kategori yang dipilih tidak tersedia.',
                'quota' => 'Kuota kandidat maksimal 1000 orang.',
                'total_compensation' => 'Total kompensasi maksimal Rp9.999.999.999.',
            ]);

        $this->assertSame(0, Project::count());
    }

    public function test_kandidat_tidak_bisa_membuat_proyek(): void
    {
        $this->actingAs($this->makeKandidat())
            ->post(route('umkm.proyek.store'), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Project::count());
    }

    public function test_umkm_memperbarui_draf_miliknya(): void
    {
        $project = $this->draft();
        $skillBaru = Skill::firstOrCreate(['name' => 'Copywriting']);

        $this->actingAs($this->umkm)
            ->from(route('umkm.proyek.edit', $project))
            ->put(route('umkm.proyek.update', $project), $this->payload([
                'title' => 'Judul Draf Sudah Diubah',
                'quota' => 3,
                'skills' => [$skillBaru->id],
            ]))
            ->assertRedirect(route('umkm.proyek.edit', $project))
            ->assertSessionHas('success', 'Draf proyek diperbarui.');

        $project->refresh();
        $this->assertSame('Judul Draf Sudah Diubah', $project->title);
        $this->assertSame(3, $project->quota);
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertEquals([$skillBaru->id], $project->skills()->pluck('skills.id')->all());
    }

    public function test_proyek_perlu_perbaikan_masih_bisa_diubah(): void
    {
        $project = $this->draft(['status' => ProjectStatus::RevisionRequested]);

        $this->actingAs($this->umkm)
            ->get(route('umkm.proyek.edit', $project))
            ->assertOk()
            ->assertSee($project->title);
    }

    public function test_proyek_yang_sudah_tayang_tidak_bisa_diubah_sebagai_draf(): void
    {
        $project = $this->makeProject([], $this->umkm->umkmProfile); // bawaan makeProject: status Published

        $this->actingAs($this->umkm)
            ->get(route('umkm.proyek.edit', $project))
            ->assertForbidden();

        $this->actingAs($this->umkm)
            ->put(route('umkm.proyek.update', $project), $this->payload(['title' => 'Tidak Boleh Berubah']))
            ->assertForbidden();

        $this->assertNotSame('Tidak Boleh Berubah', $project->fresh()->title);
    }

    public function test_umkm_lain_tidak_bisa_membuka_atau_mengubah_draf(): void
    {
        $project = $this->draft();
        $umkmLain = $this->makeUmkm();

        $this->actingAs($umkmLain)
            ->get(route('umkm.proyek.edit', $project))
            ->assertForbidden();

        $this->actingAs($umkmLain)
            ->put(route('umkm.proyek.update', $project), $this->payload(['title' => 'Diubah Orang Lain']))
            ->assertForbidden();

        $this->assertNotSame('Diubah Orang Lain', $project->fresh()->title);
    }

    /** Draf milik UMKM uji. */
    private function draft(array $overrides = []): Project
    {
        return $this->makeProject(
            array_merge(['status' => ProjectStatus::Draft, 'published_at' => null], $overrides),
            $this->umkm->umkmProfile
        );
    }

    /**
     * Isian form Buat/Ubah Draf yang valid $overrides menimpa isian tertentu.
     * Kolom skema pembayaran ikut dikirim agar test tetap lulus setelah PBI-10 digabung ke main.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Redesain Katalog Digital & Label Kemasan',
            'category_id' => $this->category->id,
            'skills' => [$this->skill->id],
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
