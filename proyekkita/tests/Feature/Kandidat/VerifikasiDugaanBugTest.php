<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsCancellationScenario;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * Verifikasi dugaan bug B1 dan B2 dari laporan audit (lihat dokumentasi-pengujian/CATATAN_TEMUAN.md).
 *
 * Setiap test menegaskan perilaku YANG BENAR. Bila dugaan bug terbukti, test GAGAL, dan kegagalan itu adalah
 * hasil verifikasinya. Jangan dihapus atau dilunakkan agar suite menjadi hijau; perbaikan kode aplikasi hanya
 * dilakukan setelah disetujui terpisah.
 *
 * Jalankan hanya test ini:  php artisan test --group=verifikasi-bug
 */
#[Group('verifikasi-bug')]
class VerifikasiDugaanBugTest extends TestCase
{
    use BuildsCancellationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;

    /** B1 (PBI-17): ?page= melebihi halaman terakhir. */
    public function test_b1_halaman_melebihi_halaman_terakhir_tidak_menampilkan_pesan_status_kosong(): void
    {
        $kandidat = $this->makeKandidat();
        $this->makeApplicationFor($kandidat);

        $html = $this->actingAs($kandidat)
        ->followingRedirects()
        ->get(route('kandidat.lamaran.index', ['page' => 2]))
        ->assertOk()
        ->getContent();

        $this->assertStringNotContainsString(
            'dengan status </b>',
            $html,
            'B1: muncul teks "Tidak ada lamaran dengan status" tanpa nama status.',
        );
        $this->assertStringNotContainsString(
            'Halaman 2 dari 1',
            $html,
            'B1: pager menampilkan nomor halaman yang melebihi jumlah halaman.',
        );
    }

    /** B2 (PBI-18): ID lamaran numerik yang melebihi batas integer PHP. */
    public function test_b2_id_lamaran_melebihi_batas_integer_menghasilkan_404(): void
    {
        $kandidat = $this->makeKandidat();
        $application = $this->makeApplicationFor($kandidat);

        $this->actingAs($kandidat)
            ->patch('/kandidat/lamaran/99999999999999999999/batal')
            ->assertNotFound();

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }
}
