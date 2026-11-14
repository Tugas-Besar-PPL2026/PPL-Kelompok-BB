<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * PBI-18 (FR-10, bagian membatalkan lamaran) - skenario tambahan yang melengkapi BatalkanLamaranTest.
 *
 * Perilaku pembatalan saat proyek sudah ditutup/lewat tenggat sengaja TIDAK dikunci di sini
 * (keputusan terbuka OD-1, lihat dokumentasi-pengujian/CATATAN_TEMUAN.md).
 */
class BatalkanLamaranEdgeTest extends TestCase
{
    use BuildsCancellationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;
    use StubsLoginRoute;

    public function test_tamu_dialihkan_ke_login_dan_status_tidak_berubah(): void
    {
        $this->stubLoginRoute();
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);

        $this->patch(route('kandidat.lamaran.cancel', $application->id))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
        $this->assertSame(1, $this->historyCountOf($application));
    }

    public function test_metode_http_selain_patch_ditolak(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);

        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $metode) {
            $this->actingAs($kandidat)
                ->call($metode, "/kandidat/lamaran/{$application->id}/batal")
                ->assertStatus(405);
        }

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
        $this->assertSame(1, $this->historyCountOf($application));
    }

    public function test_id_nol_dan_negatif_menghasilkan_404(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);

        $this->actingAs($kandidat)->patch('/kandidat/lamaran/0/batal')->assertNotFound();
        $this->actingAs($kandidat)->patch('/kandidat/lamaran/-1/batal')->assertNotFound();

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }
}
