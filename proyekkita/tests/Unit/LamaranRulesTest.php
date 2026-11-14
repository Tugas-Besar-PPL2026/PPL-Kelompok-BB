<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Http\Requests\ApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Project;
use App\Services\ApplicationCancellationService;
use App\Services\ApplicationService;
use App\Services\CandidateApplicationService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit test logika murni PBI-16, PBI-17, dan PBI-18 (tanpa HTTP dan tanpa query database).
 * Alasan Unit: aturan status, pemetaan timeline, dan parsing filter cukup diuji cepat dan terisolasi
 * untuk setiap kombinasi nilai.
 */
class LamaranRulesTest extends TestCase
{
    // ---------- PBI-18: aturan pembatalan ----------

    public static function kemampuanBatal(): array
    {
        return [
            'menunggu keputusan' => [ApplicationStatus::Pending, true],
            'terpilih sementara' => [ApplicationStatus::Shortlisted, true],
            'diterima' => [ApplicationStatus::Accepted, false],
            'ditolak' => [ApplicationStatus::Rejected, false],
            'dibatalkan' => [ApplicationStatus::Cancelled, false],
        ];
    }

    #[DataProvider('kemampuanBatal')]
    public function test_lamaran_hanya_dapat_dibatalkan_pada_status_menunggu_dan_terpilih_sementara(ApplicationStatus $status, bool $diharapkan): void
    {
        $this->assertSame($diharapkan, (new Application(['status' => $status]))->isCancellable());
    }

    public function test_pesan_alasan_tidak_dapat_dibatalkan_sesuai_status(): void
    {
        $service = new ApplicationCancellationService();

        $this->assertSame('Lamaran yang sudah diterima tidak dapat dibatalkan.', $service->reasonNotCancellable(ApplicationStatus::Accepted));
        $this->assertSame('Lamaran yang sudah ditolak tidak dapat dibatalkan.', $service->reasonNotCancellable(ApplicationStatus::Rejected));
        $this->assertSame('Lamaran ini sudah dibatalkan sebelumnya.', $service->reasonNotCancellable(ApplicationStatus::Cancelled));
        $this->assertSame('Lamaran dengan status Menunggu Keputusan tidak dapat dibatalkan.', $service->reasonNotCancellable(ApplicationStatus::Pending));
        $this->assertSame('Lamaran dengan status Terpilih Sementara tidak dapat dibatalkan.', $service->reasonNotCancellable(ApplicationStatus::Shortlisted));
    }

    // ---------- PBI-17: filter, tab, timeline ----------

    public function test_status_dari_query_hanya_menerima_nilai_enum_persis(): void
    {
        $service = new CandidateApplicationService();

        $this->assertSame(ApplicationStatus::Pending, $service->statusFromQuery('pending'));
        $this->assertSame(ApplicationStatus::Cancelled, $service->statusFromQuery('cancelled'));

        foreach (['Pending', 'PENDING', ' pending', '', 'bukan-status', null, 123, ['pending'], true] as $nilai) {
            $this->assertNull($service->statusFromQuery($nilai), 'Nilai tidak valid seharusnya diabaikan: '.json_encode($nilai));
        }
    }

    public function test_tab_status_berurutan_semua_lalu_enum_beserta_jumlah(): void
    {
        $tabs = (new CandidateApplicationService())->tabs([
            'all' => 6, 'pending' => 1, 'shortlisted' => 2, 'accepted' => 3, 'rejected' => 0, 'cancelled' => 0,
        ]);

        $this->assertSame(['', 'pending', 'shortlisted', 'accepted', 'rejected', 'cancelled'], array_column($tabs, 'key'));
        $this->assertSame(['Semua', 'Menunggu Keputusan', 'Terpilih Sementara', 'Diterima', 'Ditolak', 'Dibatalkan'], array_column($tabs, 'label'));
        $this->assertSame([6, 1, 2, 3, 0, 0], array_column($tabs, 'count'));
    }

    private function riwayat(ApplicationStatus $status, ?string $waktu, ?string $catatan = null): ApplicationStatusHistory
    {
        return new ApplicationStatusHistory(['status' => $status, 'changed_at' => $waktu, 'note' => $catatan]);
    }

    public function test_timeline_memberi_label_warna_dan_label_catatan_sesuai_status(): void
    {
        $service = new CandidateApplicationService();

        $entri = $service->timeline(new Collection([
            $this->riwayat(ApplicationStatus::Cancelled, '2026-11-06 11:30:00', 'Dibatalkan oleh kandidat.'),
            $this->riwayat(ApplicationStatus::Shortlisted, '2026-11-05 09:00:00', 'Masuk daftar pendek'),
            $this->riwayat(ApplicationStatus::Pending, '2026-11-03 20:14:00'),
        ]));

        $this->assertCount(3, $entri);

        $this->assertSame('Dibatalkan', $entri[0]['label']);
        $this->assertSame('w', $entri[0]['dot']);
        $this->assertSame('Catatan', $entri[0]['noteLabel']);
        $this->assertSame('Dibatalkan oleh kandidat.', $entri[0]['note']);
        $this->assertSame('6 Nov 2026, 11.30', $entri[0]['date']);

        $this->assertSame('Terpilih sementara', $entri[1]['label']);
        $this->assertSame('', $entri[1]['dot']);
        $this->assertSame('Catatan UMKM', $entri[1]['noteLabel']);
        $this->assertSame('Masuk daftar pendek', $entri[1]['note']);

        $this->assertSame('Lamaran dikirim', $entri[2]['label']);
        $this->assertSame('Status awal: Menunggu Keputusan', $entri[2]['detail']);
        $this->assertSame('g', $entri[2]['dot']);
        $this->assertNull($entri[2]['note']);
        $this->assertSame('3 Nov 2026, 20.14', $entri[2]['date']);
    }

    public function test_timeline_diterima_dan_ditolak_memakai_warna_masing_masing(): void
    {
        $entri = (new CandidateApplicationService())->timeline(new Collection([
            $this->riwayat(ApplicationStatus::Accepted, '2026-11-08 10:00:00'),
            $this->riwayat(ApplicationStatus::Rejected, '2026-11-07 10:00:00'),
        ]));

        $this->assertSame('Diterima', $entri[0]['label']);
        $this->assertSame('g', $entri[0]['dot']);
        $this->assertSame('Ditolak', $entri[1]['label']);
        $this->assertSame('r', $entri[1]['dot']);
    }

    public function test_timeline_entri_pending_yang_bukan_terlama_tidak_dilabeli_lamaran_dikirim(): void
    {
        $entri = (new CandidateApplicationService())->timeline(new Collection([
            $this->riwayat(ApplicationStatus::Pending, '2026-11-05 10:00:00'),
            $this->riwayat(ApplicationStatus::Pending, '2026-11-03 10:00:00'),
        ]));

        $this->assertSame('Menunggu keputusan', $entri[0]['label']);
        $this->assertSame('Lamaran dikirim', $entri[1]['label']);
    }

    public function test_timeline_tanggal_kosong_menghasilkan_null(): void
    {
        $entri = (new CandidateApplicationService())->timeline(new Collection([
            $this->riwayat(ApplicationStatus::Pending, null),
        ]));

        $this->assertNull($entri[0]['date']);
    }

    // ---------- PBI-16: kelayakan, status proyek, aturan validasi ----------

    public function test_kelayakan_profil_kosong_memerlukan_data_pribadi_dan_keterampilan(): void
    {
        $hasil = (new ApplicationService())->profileReadiness(null);

        $this->assertFalse($hasil['eligible']);
        $this->assertFalse($hasil['personal']);
        $this->assertSame(0, $hasil['skills']);
        $this->assertSame(
            ['lengkapi data pribadi & akademik di profil', 'tambahkan minimal 1 keterampilan'],
            $hasil['missing'],
        );
    }

    public function test_alasan_proyek_tidak_menerima_lamaran(): void
    {
        $service = new ApplicationService();
        $proyek = fn (ProjectStatus $status, string $tenggat) => new Project([
            'status' => $status,
            'application_deadline' => $tenggat,
        ]);

        $this->assertNull($service->closedReason($proyek(ProjectStatus::Published, now()->addDays(5)->toDateString())));
        $this->assertNull($service->closedReason($proyek(ProjectStatus::Published, now()->toDateString())), 'Hari tenggat masih diterima.');
        $this->assertSame(
            'Tenggat lamaran proyek ini sudah berakhir.',
            $service->closedReason($proyek(ProjectStatus::Published, now()->subDay()->toDateString())),
        );
        $this->assertSame(
            'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.',
            $service->closedReason($proyek(ProjectStatus::Closed, now()->addDays(5)->toDateString())),
        );
        $this->assertSame(
            'Proyek ini tidak sedang menerima lamaran.',
            $service->closedReason($proyek(ProjectStatus::Draft, now()->addDays(5)->toDateString())),
        );
    }

    public function test_aturan_validasi_pesan_lamaran(): void
    {
        $this->assertSame(50, ApplicationRequest::MIN_MESSAGE);
        $this->assertSame(5000, ApplicationRequest::MAX_MESSAGE);
        $this->assertSame(
            ['message' => ['required', 'string', 'min:50', 'max:5000']],
            (new ApplicationRequest())->rules(),
        );
    }
}
