<?php

namespace Tests\Feature\Umkm;

use App\Enums\ApplicationStatus;
use App\Enums\ModerationDecision;
use App\Enums\ProjectStatus;
use App\Models\User;
use App\Services\ProjectMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProjectCatalogScenario;
use Tests\TestCase;

/**
 * [PBI-13 / FR-06, FR-07] Halaman Proyek Saya: UMKM memantau proyek miliknya, status, jumlah pelamar,
 * dan hasil moderasi terakhir. Halaman ini hanya membaca data.
 */
class ProyekSayaTest extends TestCase
{
    use BuildsProjectCatalogScenario;
    use RefreshDatabase;

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('umkm.proyek.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_halaman_hanya_untuk_umkm(): void
    {
        $this->actingAs($this->makeKandidat())->get(route('umkm.proyek.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('umkm.proyek.index'))->assertForbidden();
    }

    public function test_umkm_tanpa_profil_usaha_ditolak(): void
    {
        $this->actingAs(User::factory()->umkm()->create())
            ->get(route('umkm.proyek.index'))
            ->assertForbidden();
    }

    // ---------- Daftar proyek ----------

    public function test_menampilkan_hanya_proyek_milik_umkm_yang_login(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Proyek Saya Satu', [], null, [], $umkm->umkmProfile);
        $this->makeCatalogProject('Proyek Saya Dua', ['status' => ProjectStatus::Draft, 'published_at' => null], null, [], $umkm->umkmProfile);
        $this->makeCatalogProject('Proyek Milik Orang Lain');

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertOk()
            ->assertSee('Proyek Saya Satu')
            ->assertSee('Proyek Saya Dua')
            ->assertDontSee('Proyek Milik Orang Lain');
    }

    public function test_proyek_dari_semua_status_tampil_termasuk_yang_belum_dipublikasikan(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;

        foreach (ProjectStatus::cases() as $status) {
            $this->makeCatalogProject('Proyek '.$status->value, ['status' => $status], null, [], $profil);
        }

        $response = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->assertOk();

        foreach (ProjectStatus::cases() as $status) {
            $response->assertSee('Proyek '.$status->value);
        }
        $this->assertSame(count(ProjectStatus::cases()), $response->viewData('projects')->total());
    }

    public function test_kartu_proyek_menampilkan_kategori_kompensasi_kuota_dan_tenggat(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Redesain Label', [
            'total_compensation' => 3000000,
            'quota' => 4,
            'application_deadline' => '2026-12-20',
        ], $this->makeCategory('Desain Grafis & Branding'), [], $umkm->umkmProfile);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertSee('Desain Grafis & Branding')
            ->assertSee('Rp3.000.000')
            ->assertSee('4 orang')
            ->assertSee('20 Des 2026');
    }

    public function test_pesan_kosong_dan_tombol_buat_proyek_untuk_umkm_tanpa_proyek(): void
    {
        $this->actingAs($this->makeUmkm())
            ->get(route('umkm.proyek.index'))
            ->assertOk()
            ->assertSee('Belum ada proyek')
            ->assertSee(route('umkm.proyek.create'), false);
    }

    // ---------- Ringkasan metrik ----------

    public function test_metrik_menghitung_proyek_per_status_dan_total_pelamar(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;

        $terbit = $this->makeCatalogProject('Terbit Satu', [], null, [], $profil);
        $this->makeCatalogProject('Terbit Dua', [], null, [], $profil);
        $this->makeCatalogProject('Menunggu', ['status' => ProjectStatus::PendingReview], null, [], $profil);
        $this->makeCatalogProject('Perbaikan', ['status' => ProjectStatus::RevisionRequested], null, [], $profil);
        $this->makeCatalogProject('Draf', ['status' => ProjectStatus::Draft], null, [], $profil);

        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $terbit]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Shortlisted, ['project' => $terbit]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Accepted, ['project' => $terbit]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Rejected, ['project' => $terbit]);

        $response = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->assertOk();
        $counts = $response->viewData('counts');

        $this->assertSame(2, $counts['published']);
        $this->assertSame(1, $counts['pending_review']);
        $this->assertSame(1, $counts['revision_requested']);
        $this->assertSame(1, $counts['draft']);
        $this->assertSame(0, $counts['rejected']);
        $this->assertSame(0, $counts['closed']);
        $this->assertSame(5, $counts['all']);
        $this->assertSame(4, $response->viewData('totalApplicants'));
    }

    public function test_lamaran_yang_dibatalkan_tidak_dihitung_sebagai_pelamar(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeCatalogProject('Proyek Ramai', [], null, [], $umkm->umkmProfile);

        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $project]);
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Cancelled, ['project' => $project]);

        $response = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->assertOk();

        $this->assertSame(1, $response->viewData('totalApplicants'));
        $this->assertSame(1, $response->viewData('projects')->first()->applicants_count);
    }

    public function test_metrik_dan_pelamar_tidak_mencakup_proyek_umkm_lain(): void
    {
        $umkm = $this->makeUmkm();
        $lain = $this->makeCatalogProject('Proyek UMKM Lain');
        $this->makeApplicationFor($this->makeKandidat(), ApplicationStatus::Pending, ['project' => $lain]);

        $response = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->assertOk();

        $this->assertSame(0, $response->viewData('counts')['all']);
        $this->assertSame(0, $response->viewData('totalApplicants'));
    }

    // ---------- Tab status ----------

    public function test_filter_status_hanya_menampilkan_proyek_dengan_status_tersebut(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;
        $this->makeCatalogProject('Proyek Terbit', [], null, [], $profil);
        $this->makeCatalogProject('Proyek Draf', ['status' => ProjectStatus::Draft], null, [], $profil);
        $this->makeCatalogProject('Proyek Ditutup', ['status' => ProjectStatus::Closed], null, [], $profil);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('Proyek Draf')
            ->assertDontSee('Proyek Terbit')
            ->assertDontSee('Proyek Ditutup');

        $this->get(route('umkm.proyek.index', ['status' => 'closed']))
            ->assertSee('Proyek Ditutup')
            ->assertDontSee('Proyek Draf');
    }

    public function test_status_yang_tidak_dikenal_dianggap_tanpa_filter(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Proyek A', [], null, [], $umkm->umkmProfile);
        $this->makeCatalogProject('Proyek B', ['status' => ProjectStatus::Draft], null, [], $umkm->umkmProfile);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index', ['status' => 'ngawur']))
            ->assertOk()
            ->assertSee('Proyek A')
            ->assertSee('Proyek B');
    }

    public function test_tab_tanpa_proyek_menampilkan_pesan_kosong_bukan_galat(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Proyek Terbit', [], null, [], $umkm->umkmProfile);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index', ['status' => 'rejected']))
            ->assertOk()
            ->assertSee('Tidak ada proyek dengan status Ditolak')
            ->assertDontSee('Proyek Terbit');
    }

    public function test_setiap_status_punya_tab_dengan_jumlahnya(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Proyek Terbit', [], null, [], $umkm->umkmProfile);

        $html = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->getContent();

        $this->assertStringContainsString('dusk="tab-semua"', $html);
        foreach (ProjectStatus::cases() as $status) {
            $this->assertStringContainsString('dusk="tab-'.$status->value.'"', $html);
        }
    }

    // ---------- Hasil moderasi ----------

    public function test_catatan_moderasi_terakhir_ditampilkan_dan_yang_lama_tidak(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeCatalogProject('Proyek Bolak-balik', [], null, [], $umkm->umkmProfile);

        $this->makeModeration($project, ModerationDecision::RevisionRequested, 'Catatan lama yang sudah tidak berlaku.');
        $this->makeModeration($project, ModerationDecision::Approved, 'Catatan terbaru, proyek sudah sesuai.');

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertSee('Hasil moderasi terakhir: Disetujui')
            ->assertSee('Catatan terbaru, proyek sudah sesuai.')
            ->assertDontSee('Catatan lama yang sudah tidak berlaku.');
    }

    public function test_keputusan_perbaikan_dan_penolakan_menampilkan_catatan_admin(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;
        $perbaikan = $this->makeCatalogProject('Proyek Perlu Perbaikan', ['status' => ProjectStatus::RevisionRequested], null, [], $profil);
        $ditolak = $this->makeCatalogProject('Proyek Ditolak', ['status' => ProjectStatus::Rejected], null, [], $profil);

        $this->makeModeration($perbaikan, ModerationDecision::RevisionRequested, 'Perjelas target luaran dan rincian termin.');
        $this->makeModeration($ditolak, ModerationDecision::Rejected, 'Deskripsi pekerjaan tidak sesuai ketentuan platform.');

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertSee('Hasil moderasi terakhir: Minta Perbaikan')
            ->assertSee('Perjelas target luaran dan rincian termin.')
            ->assertSee('Hasil moderasi terakhir: Ditolak')
            ->assertSee('Deskripsi pekerjaan tidak sesuai ketentuan platform.');
    }

    public function test_keputusan_tanpa_catatan_menampilkan_keterangan_pengganti(): void
    {
        $umkm = $this->makeUmkm();
        $project = $this->makeCatalogProject('Proyek Tanpa Catatan', [], null, [], $umkm->umkmProfile);
        $this->makeModeration($project, ModerationDecision::Approved, null);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertSee('Admin tidak menyertakan catatan.');
    }

    public function test_proyek_yang_belum_pernah_dimoderasi_tidak_punya_kotak_catatan(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Draf Baru', ['status' => ProjectStatus::Draft, 'published_at' => null], null, [], $umkm->umkmProfile);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertOk()
            ->assertDontSee('Hasil moderasi terakhir');
    }

    public function test_catatan_moderasi_milik_proyek_lain_tidak_tercampur(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;
        $a = $this->makeCatalogProject('Proyek A', [], null, [], $profil);
        $this->makeCatalogProject('Proyek B', [], null, [], $profil);
        $this->makeModeration($a, ModerationDecision::Approved, 'Catatan khusus proyek A.');

        $html = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'Catatan khusus proyek A.'));
    }

    // ---------- Aksi pada kartu ----------

    public function test_aksi_pada_kartu_sesuai_status_proyek(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;
        $draf = $this->makeCatalogProject('Proyek Draf', ['status' => ProjectStatus::Draft], null, [], $profil);
        $perbaikan = $this->makeCatalogProject('Proyek Perbaikan', ['status' => ProjectStatus::RevisionRequested], null, [], $profil);
        $menunggu = $this->makeCatalogProject('Proyek Menunggu', ['status' => ProjectStatus::PendingReview], null, [], $profil);
        $ditolak = $this->makeCatalogProject('Proyek Ditolak', ['status' => ProjectStatus::Rejected], null, [], $profil);
        $terbit = $this->makeCatalogProject('Proyek Terbit', [], null, [], $profil);
        $ditutup = $this->makeCatalogProject('Proyek Ditutup', ['status' => ProjectStatus::Closed], null, [], $profil);

        $html = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->getContent();

        $this->assertStringContainsString('dusk="lanjutkan-draf-'.$draf->id.'"', $html);
        $this->assertStringContainsString('dusk="perbaiki-'.$perbaikan->id.'"', $html);
        $this->assertStringContainsString('dusk="lihat-pelamar-proyek-'.$terbit->id.'"', $html);
        $this->assertStringContainsString('dusk="lihat-pelamar-proyek-'.$ditutup->id.'"', $html);

        foreach ([$menunggu, $ditolak] as $tanpaAksi) {
            $this->assertStringNotContainsString('-'.$tanpaAksi->id.'"', $this->tombolAksi($html, $tanpaAksi->id));
        }
        $this->assertStringContainsString('Admin sedang memeriksa proyek ini.', $html);
        $this->assertStringContainsString('Proyek ditolak dan tidak ditampilkan kepada kandidat.', $html);
    }

    public function test_tombol_aksi_menuju_halaman_yang_benar(): void
    {
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;
        $draf = $this->makeCatalogProject('Proyek Draf', ['status' => ProjectStatus::Draft], null, [], $profil);
        $terbit = $this->makeCatalogProject('Proyek Terbit', [], null, [], $profil);

        $this->actingAs($umkm)
            ->get(route('umkm.proyek.index'))
            ->assertSee(route('umkm.proyek.edit', $draf), false)
            ->assertSee(route('umkm.seleksi.index', ['proyek' => $terbit->id]), false);
    }

    // ---------- Pagination ----------

    public function test_daftar_dipecah_10_proyek_per_halaman_dan_filter_status_terbawa(): void
    {
        $umkm = $this->makeUmkm();
        for ($i = 1; $i <= 11; $i++) {
            $this->makeCatalogProject("Proyek Draf {$i}", ['status' => ProjectStatus::Draft], null, [], $umkm->umkmProfile);
        }

        $halaman1 = $this->actingAs($umkm)->get(route('umkm.proyek.index', ['status' => 'draft']))->assertOk();
        $this->assertCount(10, $halaman1->viewData('projects')->items());
        $this->assertSame(11, $halaman1->viewData('projects')->total());
        $this->assertStringContainsString('status=draft', $halaman1->viewData('projects')->nextPageUrl());

        $halaman2 = $this->get(route('umkm.proyek.index', ['status' => 'draft', 'page' => 2]))->assertOk();
        $this->assertCount(1, $halaman2->viewData('projects')->items());
        $halaman2->assertSee('Halaman 2 dari 2');
    }

    public function test_proyek_terbaru_tampil_paling_atas(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Proyek Pertama', [], null, [], $umkm->umkmProfile);
        $this->makeCatalogProject('Proyek Kedua', [], null, [], $umkm->umkmProfile);

        $judul = $this->actingAs($umkm)->get(route('umkm.proyek.index'))->viewData('projects')->pluck('title')->all();

        $this->assertSame(['Proyek Kedua', 'Proyek Pertama'], $judul);
    }

    // ---------- Service ----------

    public function test_service_menghitung_status_dengan_nol_untuk_status_yang_kosong(): void
    {
        $umkm = $this->makeUmkm();
        $this->makeCatalogProject('Satu-satunya', ['status' => ProjectStatus::Closed], null, [], $umkm->umkmProfile);

        $counts = app(ProjectMonitoringService::class)->statusCounts($umkm->umkmProfile);

        $this->assertSame(1, $counts['closed']);
        $this->assertSame(1, $counts['all']);
        foreach (ProjectStatus::cases() as $status) {
            $this->assertArrayHasKey($status->value, $counts);
        }
    }

    public function test_service_menentukan_aksi_sesuai_status(): void
    {
        $service = app(ProjectMonitoringService::class);
        $umkm = $this->makeUmkm();
        $profil = $umkm->umkmProfile;

        $draf = $this->makeCatalogProject('Draf', ['status' => ProjectStatus::Draft], null, [], $profil);
        $perbaikan = $this->makeCatalogProject('Perbaikan', ['status' => ProjectStatus::RevisionRequested], null, [], $profil);
        $terbit = $this->makeCatalogProject('Terbit', [], null, [], $profil);
        $menunggu = $this->makeCatalogProject('Menunggu', ['status' => ProjectStatus::PendingReview], null, [], $profil);
        $ditolak = $this->makeCatalogProject('Ditolak', ['status' => ProjectStatus::Rejected], null, [], $profil);

        $this->assertSame('Lanjutkan Draf', $service->actionFor($draf)['label']);
        $this->assertSame('Perbaiki & Ajukan Ulang', $service->actionFor($perbaikan)['label']);
        $this->assertSame('Lihat Pelamar', $service->actionFor($terbit)['label']);
        $this->assertNull($service->actionFor($menunggu));
        $this->assertNull($service->actionFor($ditolak));
    }

    /** Potongan HTML tombol aksi milik satu proyek (kosong bila proyek tidak punya tombol). */
    private function tombolAksi(string $html, int $projectId): string
    {
        preg_match_all('/<a [^>]*dusk="(?:lanjutkan-draf|perbaiki|lihat-pelamar-proyek)-'.$projectId.'"[^>]*>/', $html, $m);

        return implode('', $m[0]);
    }
}
