<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Application;
use App\Models\Project;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\Concerns\StubsLoginRoute;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * PBI-16 (FR-09) - skenario boundary, edge case, dan keamanan yang melengkapi AjukanLamaranTest.
 *
 * Tidak mengulang skenario yang sudah ada di AjukanLamaranTest (alur sukses, pesan kosong, 49/50 karakter,
 * lamaran ganda, peran non-kandidat, tenggat, dsb.).
 */
class AjukanLamaranEdgeTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase, UsesInMemoryDatabase;
    use StubsLoginRoute;

    private function kirim(User $user, Project $project, mixed $message, array $extra = [])
    {
        return $this->actingAs($user)->post(
            route('kandidat.proyek.lamaran.store', $project),
            ['message' => $message] + $extra,
        );
    }

    public function test_pesan_tepat_5000_karakter_diterima_dan_5001_ditolak(): void
    {
        $user = $this->makeKandidat();
        $projectA = $this->makeProject();
        $projectB = $this->makeProject();

        $this->kirim($user, $projectA, str_repeat('a', 5001))
            ->assertSessionHasErrors(['message' => 'Pesan lamaran maksimal 5000 karakter.']);
        $this->assertSame(0, Application::count());

        $this->kirim($user, $projectB, str_repeat('a', 5000))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(1, Application::count());
        $this->assertSame(5000, mb_strlen(Application::sole()->message));
    }

    public function test_pesan_multibyte_dihitung_per_karakter_bukan_per_byte(): void
    {
        $user = $this->makeKandidat();
        $projectA = $this->makeProject();
        $projectB = $this->makeProject();

        // 49 karakter "é" = 98 byte; tetap kurang dari 50 karakter -> ditolak.
        $this->kirim($user, $projectA, str_repeat('é', 49))
            ->assertSessionHasErrors(['message' => 'Pesan lamaran minimal 50 karakter.']);
        $this->assertSame(0, Application::count());

        // 50 karakter "é" = 100 byte -> diterima.
        $this->kirim($user, $projectB, str_repeat('é', 50))->assertSessionHasNoErrors();
        $this->assertSame(1, Application::count());
    }

    public function test_spasi_di_awal_dan_akhir_dibuang_sebelum_validasi_dan_penyimpanan(): void
    {
        $user = $this->makeKandidat();
        $projectA = $this->makeProject();
        $projectB = $this->makeProject();

        // 49 karakter + spasi: setelah trim hanya 49 karakter -> ditolak.
        $this->kirim($user, $projectA, str_repeat('a', 49).'     ')
            ->assertSessionHasErrors(['message' => 'Pesan lamaran minimal 50 karakter.']);
        $this->kirim($user, $projectA, '     '.str_repeat('a', 49))
            ->assertSessionHasErrors(['message' => 'Pesan lamaran minimal 50 karakter.']);
        $this->assertSame(0, Application::count());

        // 50 karakter dengan spasi di kedua sisi -> diterima dan tersimpan tanpa spasi tepi.
        $this->kirim($user, $projectB, '   '.str_repeat('a', 50).'   ')->assertSessionHasNoErrors();
        $this->assertSame(str_repeat('a', 50), Application::sole()->message);
    }

    public function test_pesan_berupa_array_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, ['a', 'b'])->assertSessionHasErrors('message');

        $this->assertSame(0, Application::count());
    }

    public function test_kolom_status_dan_kandidat_dari_request_diabaikan(): void
    {
        $user = $this->makeKandidat();
        $lain = $this->makeKandidat();
        $project = $this->makeProject();
        $proyekLain = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage(), [
            'status' => ApplicationStatus::Accepted->value,
            'candidate_profile_id' => $lain->candidateProfile->id,
            'project_id' => $proyekLain->id,
            'selection_note' => 'Disisipkan oleh pelamar',
        ])->assertSessionHas('success');

        $application = Application::sole();
        $this->assertSame($project->id, $application->project_id);
        $this->assertSame($user->candidateProfile->id, $application->candidate_profile_id);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertNull($application->selection_note);
    }

    public function test_proyek_belum_dipublikasikan_menghasilkan_404_saat_mengirim(): void
    {
        $user = $this->makeKandidat();

        foreach ([ProjectStatus::PendingReview, ProjectStatus::RevisionRequested, ProjectStatus::Rejected] as $status) {
            $project = $this->makeProject(['status' => $status]);

            $this->kirim($user, $project, $this->validMessage())->assertNotFound();
        }

        $this->assertSame(0, Application::count());
    }

    public function test_setiap_kolom_data_pribadi_yang_kosong_atau_hanya_spasi_membuat_profil_tidak_memenuhi_syarat(): void
    {
        foreach (ApplicationService::PERSONAL_FIELDS as $field) {
            foreach (['', '   '] as $nilai) {
                $user = $this->makeKandidat([$field => $nilai]);
                $project = $this->makeProject();

                $this->kirim($user, $project, $this->validMessage())
                    ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'data pribadi & akademik'));

                $this->assertSame(0, Application::count(), "Kolom {$field} bernilai \"{$nilai}\" seharusnya membuat lamaran ditolak.");
            }
        }
    }

    public function test_pesan_berisi_html_ditampilkan_sebagai_teks_pada_kartu_lamaran(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();
        $pesan = '</textarea><script>alert(1)</script> '.str_repeat('x', 40);

        $this->kirim($user, $project, $pesan)->assertSessionHas('success');

        $this->get(route('kandidat.proyek.show', $project))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_proyek_ditutup_setelah_halaman_dibuka_membuat_pengiriman_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();
        $halaman = route('kandidat.proyek.show', $project);

        // Halaman dibuka saat proyek masih menerima lamaran.
        $this->actingAs($user)->get($halaman)->assertOk()->assertSee('Proyek masih menerima lamaran');

        // Sebelum form dikirim, UMKM menutup proyek.
        Project::whereKey($project->id)->update(['status' => ProjectStatus::Closed->value]);

        $this->kirim($user, $project, $this->validMessage())
            ->assertRedirect($halaman)
            ->assertSessionHas('error', 'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.');

        $this->assertSame(0, Application::count());
        $this->get($halaman)->assertSee('Pengajuan lamaran dinonaktifkan');
    }

    public function test_tenggat_lewat_setelah_halaman_dibuka_membuat_pengiriman_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->actingAs($user)->get(route('kandidat.proyek.show', $project))->assertOk();

        Project::whereKey($project->id)->update(['application_deadline' => now()->subDay()->toDateString()]);

        $this->kirim($user, $project, $this->validMessage())
            ->assertSessionHas('error', 'Tenggat lamaran proyek ini sudah berakhir.');

        $this->assertSame(0, Application::count());
    }

    public function test_tamu_dialihkan_ke_login_dan_tidak_ada_lamaran_tersimpan(): void
    {
        $this->stubLoginRoute();
        $project = $this->makeProject();

        $this->post(route('kandidat.proyek.lamaran.store', $project), ['message' => $this->validMessage()])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, Application::count());
    }

    public function test_kandidat_yang_sama_boleh_melamar_dua_proyek_berbeda(): void
    {
        $user = $this->makeKandidat();
        $projectA = $this->makeProject();
        $projectB = $this->makeProject();

        $this->kirim($user, $projectA, $this->validMessage())->assertSessionHas('success');
        $this->kirim($user, $projectB, $this->validMessage())->assertSessionHas('success');

        $this->assertSame(2, Application::count());
        $this->assertEqualsCanonicalizing(
            [$projectA->id, $projectB->id],
            Application::pluck('project_id')->all(),
        );
    }
}
