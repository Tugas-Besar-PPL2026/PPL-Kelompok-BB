<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Services\CandidateApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * PBI-17 (FR-10, bagian memantau status dan riwayat) - skenario tambahan yang melengkapi LamaranSayaTest.
 */
class LamaranSayaEdgeTest extends TestCase
{
    use BuildsCancellationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;
    use StubsLoginRoute;

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->stubLoginRoute();

        $this->get(route('kandidat.lamaran.index'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_tepat_sepuluh_lamaran_tidak_menampilkan_pagination(): void
    {
        $this->assertSame(10, CandidateApplicationService::PER_PAGE, 'Skenario ini mengasumsikan 10 lamaran per halaman.');

        $kandidat = $this->makeKandidat();
        foreach (range(1, CandidateApplicationService::PER_PAGE) as $i) {
            $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, [
                'title' => sprintf('Proyek-%02d', $i),
                'created_at' => sprintf('2026-11-%02d 09:00:00', $i),
            ]);
        }

        $response = $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))->assertOk();

        foreach (range(1, CandidateApplicationService::PER_PAGE) as $i) {
            $response->assertSee(sprintf('Proyek-%02d', $i));
        }
        $response->assertDontSee('Halaman 1 dari')->assertDontSee('Berikutnya');
    }

    public function test_lamaran_pada_proyek_yang_sudah_ditutup_tetap_tampil(): void
    {
        $kandidat = $this->makeKandidat();
        $project = $this->makeProject(['title' => 'Proyek Sudah Ditutup', 'status' => ProjectStatus::Closed]);
        $this->makeApplicationFor($kandidat, ApplicationStatus::Pending, ['project' => $project]);

        $this->actingAs($kandidat)->get(route('kandidat.lamaran.index'))
            ->assertOk()
            ->assertSee('Proyek Sudah Ditutup')
            ->assertSee('Menunggu Keputusan');
    }
}
