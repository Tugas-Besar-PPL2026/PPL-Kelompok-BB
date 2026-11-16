<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Services\ProjectDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjectCatalogScenario;
use Tests\TestCase;

/**
 * [PBI-14 / FR-08] Kasus tambahan untuk daftar dan detail proyek, melengkapi DetailProyekTest:
 * akses tamu, pagination, urutan, sisa kuota, batas tenggat, proyek ditutup, lencana verifikasi,
 * rating UMKM, dan fungsi bantu pada ProjectDiscoveryService.
 */
class EksplorProyekEdgeTest extends TestCase
{
    use BuildsProjectCatalogScenario;
    use RefreshDatabase;

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $project = $this->makeProject();

        $this->get(route('kandidat.proyek.index'))->assertRedirect(route('login'));
        $this->get(route('kandidat.proyek.show', $project))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---------- Daftar ----------

    public function test_daftar_dipecah_10_proyek_per_halaman(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makeCatalogProject("Proyek Nomor {$i}");
        }

        $halaman1 = $this->actingAs($this->makeKandidat())->get(route('kandidat.proyek.index'))->assertOk();
        $this->assertCount(10, $halaman1->viewData('projects')->items());
        $this->assertSame(11, $halaman1->viewData('projects')->total());

        $halaman2 = $this->get(route('kandidat.proyek.index', ['page' => 2]))->assertOk();
        $this->assertCount(1, $halaman2->viewData('projects')->items());
    }

    public function test_proyek_terbaru_dipublikasikan_tampil_paling_atas(): void
    {
        $this->makeCatalogProject('Terbit Lama', ['published_at' => now()->subDays(9)]);
        $this->makeCatalogProject('Terbit Baru', ['published_at' => now()->subDay()]);
        $this->makeCatalogProject('Terbit Tengah', ['published_at' => now()->subDays(4)]);

        $judul = $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->viewData('projects')->pluck('title')->all();

        $this->assertSame(['Terbit Baru', 'Terbit Tengah', 'Terbit Lama'], $judul);
    }

    public function test_proyek_dengan_tenggat_hari_ini_masih_tampil_dan_kemarin_tidak(): void
    {
        $this->makeCatalogProject('Tenggat Hari Ini', ['application_deadline' => now()->toDateString()]);
        $this->makeCatalogProject('Tenggat Kemarin', ['application_deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->assertSee('Tenggat Hari Ini')
            ->assertDontSee('Tenggat Kemarin');
    }

    public function test_proyek_menunggu_moderasi_dan_ditolak_tidak_tampil_di_daftar(): void
    {
        $this->makeCatalogProject('Proyek Menunggu', ['status' => ProjectStatus::PendingReview]);
        $this->makeCatalogProject('Proyek Perlu Perbaikan', ['status' => ProjectStatus::RevisionRequested]);
        $this->makeCatalogProject('Proyek Ditolak', ['status' => ProjectStatus::Rejected]);
        $this->makeCatalogProject('Proyek Terbuka');

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->assertSee('Proyek Terbuka')
            ->assertDontSee('Proyek Menunggu')
            ->assertDontSee('Proyek Perlu Perbaikan')
            ->assertDontSee('Proyek Ditolak');
    }

    public function test_kartu_menampilkan_kategori_keterampilan_dan_tenggat(): void
    {
        $this->makeCatalogProject(
            'Poster Promosi',
            ['application_deadline' => '2099-12-20'],
            $this->makeCategory('Pemasaran Digital'),
            [$this->makeSkill('Canva'), $this->makeSkill('Copywriting')],
        );

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->assertSee('Pemasaran Digital')
            ->assertSee('Canva')
            ->assertSee('Copywriting')
            ->assertSee('20 Des 2099');
    }

    // ---------- Sisa kuota ----------

    public function test_sisa_kuota_berkurang_hanya_oleh_lamaran_yang_diterima(): void
    {
        $project = $this->makeProject(['quota' => 3]);

        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Accepted, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Shortlisted, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Rejected, ['project' => $project]);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.index'))
            ->assertSee('2 dari 3 orang');
    }

    public function test_sisa_kuota_tidak_pernah_negatif(): void
    {
        $project = $this->makeProject(['quota' => 1]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Accepted, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Accepted, ['project' => $project]);

        $this->assertSame(0, app(ProjectDiscoveryService::class)->remainingQuota($project));
    }

    // ---------- Detail ----------

    public function test_detail_proyek_ditutup_masih_dapat_dibuka_tetapi_tidak_ada_di_daftar(): void
    {
        $closed = $this->makeCatalogProject('Proyek Sudah Ditutup', ['status' => ProjectStatus::Closed]);
        $user = $this->makeKandidat();

        $this->actingAs($user)->get(route('kandidat.proyek.show', $closed))
            ->assertOk()
            ->assertSee('Proyek Sudah Ditutup')
            ->assertSee('Ditutup');

        $this->get(route('kandidat.proyek.index'))->assertDontSee('Proyek Sudah Ditutup');
    }

    public function test_detail_pada_hari_terakhir_tenggat_menampilkan_keterangan_hari_ini(): void
    {
        $project = $this->makeProject(['application_deadline' => now()->toDateString()]);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('Hari ini hari terakhir');
    }

    public function test_detail_menampilkan_semua_termin_beserta_porsinya(): void
    {
        $project = $this->makeProject(['total_compensation' => 3000000]);
        $project->paymentTerms()->delete();
        $project->paymentTerms()->create(['term_number' => 1, 'amount' => 1000000, 'condition' => 'Uang muka']);
        $project->paymentTerms()->create(['term_number' => 2, 'amount' => 2000000, 'condition' => 'Pelunasan']);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertSee('Termin 1')->assertSee('Rp1.000.000')->assertSee('33%')
            ->assertSee('Termin 2')->assertSee('Rp2.000.000')->assertSee('67%');
    }

    public function test_detail_menampilkan_keterampilan_dan_target_penyelesaian(): void
    {
        $project = $this->makeCatalogProject(
            'Proyek Bertarget',
            ['target_completion_date' => '2099-01-15'],
            null,
            [$this->makeSkill('Illustrator')],
        );

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertSee('Illustrator')
            ->assertSee('15 Jan 2099');
    }

    // ---------- Kartu UMKM ----------

    public function test_lencana_terverifikasi_hanya_untuk_umkm_yang_sudah_diverifikasi(): void
    {
        $belum = $this->makeUmkm(['business_name' => 'Toko Belum Verifikasi', 'verification_status' => VerificationStatus::Pending]);
        $project = $this->makeProject([], $belum->umkmProfile);

        $user = $this->makeKandidat();
        $this->actingAs($user)->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('Toko Belum Verifikasi')
            ->assertDontSee('UMKM Terverifikasi');

        $this->get(route('kandidat.proyek.index'))
            ->assertSee('Toko Belum Verifikasi')
            ->assertDontSee('UMKM Terverifikasi');
    }

    public function test_rata_rata_penilaian_umkm_dihitung_dari_evaluasi_yang_diterima(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeProject([], $umkm->umkmProfile);
        $this->makeEvaluationFor($umkm, 5);
        $this->makeEvaluationFor($umkm, 4);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertSee('4,5')
            ->assertSee('2 evaluasi')
            ->assertDontSee('Belum ada penilaian');
    }

    public function test_evaluasi_umkm_lain_tidak_mempengaruhi_rating(): void
    {
        $umkm = $this->makeUmkm();
        $lain = $this->makeUmkm();
        $project = $this->makeProject([], $umkm->umkmProfile);
        $this->makeEvaluationFor($lain, 5);

        $this->actingAs($this->makeKandidat())
            ->get(route('kandidat.proyek.show', $project))
            ->assertSee('Belum ada penilaian');
    }

    // ---------- Service ----------

    public function test_service_memecah_hasil_pekerjaan_per_baris_tanpa_tanda_poin(): void
    {
        $project = $this->makeProject(['expected_output' => "• Logo baru\n- Panduan merek\n* File sumber\n\nKatalog 8 halaman"]);

        $this->assertSame(
            ['Logo baru', 'Panduan merek', 'File sumber', 'Katalog 8 halaman'],
            app(ProjectDiscoveryService::class)->expectedOutputItems($project),
        );
    }

    public function test_service_membuat_keterangan_sisa_waktu_tenggat(): void
    {
        $service = app(ProjectDiscoveryService::class);

        $this->assertSame('5 hari lagi', $service->deadlineNote($this->makeProject(['application_deadline' => now()->addDays(5)->toDateString()])));
        $this->assertSame('Hari ini hari terakhir', $service->deadlineNote($this->makeProject(['application_deadline' => now()->toDateString()])));
        $this->assertSame('Berakhir 3 hari lalu', $service->deadlineNote($this->makeProject(['application_deadline' => now()->subDays(3)->toDateString()])));
    }

    public function test_service_hanya_mengizinkan_detail_untuk_proyek_dipublikasikan_atau_ditutup(): void
    {
        $this->assertCount(2, ProjectDiscoveryService::VIEWABLE);
        $this->assertContains(ProjectStatus::Published, ProjectDiscoveryService::VIEWABLE);
        $this->assertContains(ProjectStatus::Closed, ProjectDiscoveryService::VIEWABLE);
    }
}
