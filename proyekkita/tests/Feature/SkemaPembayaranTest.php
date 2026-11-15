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
 * Pengujian skema pembayaran proyek: cara pembayaran, termin, dan kesesuaian jumlah termin dengan total kompensasi.
 */
class SkemaPembayaranTest extends TestCase
{
    use RefreshDatabase, UsesInMemoryDatabase, BuildsApplicationScenario;

    private User $umkm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkm = $this->makeUmkm();
    }

    public function test_bertahap_dua_termin_tersimpan_berurutan(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $project = Project::firstOrFail();
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertEquals([1, 2], $project->paymentTerms()->pluck('term_number')->all());
        $this->assertEquals(2500000, (float) $project->paymentTerms()->sum('amount'));
    }

    public function test_sekali_bayar_menyimpan_satu_termin_sebesar_total(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload([
                'payment_method' => 'sekali',
                'terms' => [['amount' => 2500000, 'condition' => 'Pelunasan setelah hasil akhir disetujui UMKM']],
            ]))
            ->assertSessionHasNoErrors();

        $term = Project::firstOrFail()->paymentTerms()->sole();
        $this->assertSame(1, $term->term_number);
        $this->assertEquals(2500000, (float) $term->amount);
    }

    public function test_jumlah_termin_harus_sama_dengan_total_kompensasi(): void
    {
        $this->actingAs($this->umkm)
            ->from(route('umkm.proyek.create'))
            ->post(route('umkm.proyek.store'), $this->payload([
                'terms' => [
                    ['amount' => 1000000, 'condition' => 'Uang muka'],
                    ['amount' => 1000000, 'condition' => 'Pelunasan'],
                ],
            ]))
            ->assertRedirect(route('umkm.proyek.create'))
            ->assertSessionHasErrors(['terms' => 'Jumlah nominal termin harus sama dengan total kompensasi.']);

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('payment_terms', 0);
    }

    public function test_bertahap_minimal_dua_termin(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload([
                'terms' => [['amount' => 2500000, 'condition' => 'Pelunasan']],
            ]))
            ->assertSessionHasErrors(['terms' => 'Pembayaran bertahap minimal terdiri dari dua termin.']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_nominal_dan_kondisi_termin_wajib_valid(): void
    {
        $this->actingAs($this->umkm)
            ->post(route('umkm.proyek.store'), $this->payload([
                'terms' => [
                    ['amount' => 0, 'condition' => 'Uang muka'],
                    ['amount' => 2500000, 'condition' => ''],
                ],
            ]))
            ->assertSessionHasErrors(['terms.0.amount', 'terms.1.condition']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_ubah_draf_mengganti_termin_lama(): void
    {
        $project = $this->makeProject(
            ['status' => ProjectStatus::Draft, 'published_at' => null],
            $this->umkm->umkmProfile
        );

        $this->actingAs($this->umkm)
            ->put(route('umkm.proyek.update', $project), $this->payload([
                'payment_method' => 'sekali',
                'terms' => [['amount' => 2500000, 'condition' => 'Pelunasan setelah hasil akhir disetujui UMKM']],
            ]))
            ->assertSessionHasNoErrors();

        $term = $project->paymentTerms()->sole();
        $this->assertSame(1, $term->term_number);
        $this->assertEquals(2500000, (float) $term->amount);
    }

    /** Isian form proyek yang valid dengan dua termin; $overrides menimpa isian tertentu. */
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
