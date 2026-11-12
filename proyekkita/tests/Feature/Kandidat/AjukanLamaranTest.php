<?php

namespace Tests\Feature\Kandidat;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Exceptions\ApplicationNotAllowedException;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Services\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsApplicationScenario;
use Tests\TestCase;

/** [PBI-16 / FR-09] Pengajuan lamaran oleh kandidat. */
class AjukanLamaranTest extends TestCase
{
    use BuildsApplicationScenario;
    use RefreshDatabase;

    private function kirim($user, $project, ?string $message)
    {
        return $this->actingAs($user)->post(route('kandidat.proyek.lamaran.store', $project), ['message' => $message]);
    }

    public function test_kandidat_berhasil_mengirim_lamaran_dengan_status_awal_menunggu_keputusan(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage())
            ->assertRedirect(route('kandidat.proyek.show', $project))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $application = Application::sole();
        $this->assertSame($project->id, $application->project_id);
        $this->assertSame($user->candidateProfile->id, $application->candidate_profile_id);
        $this->assertSame($this->validMessage(), $application->message);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertSame('Menunggu Keputusan', $application->status->label());
        $this->assertSame('pending', DB::table('applications')->value('status'));
    }

    public function test_riwayat_status_awal_dicatat_satu_kali(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage());

        $history = ApplicationStatusHistory::sole();
        $this->assertSame(Application::sole()->id, $history->application_id);
        $this->assertSame(ApplicationStatus::Pending, $history->status);
        $this->assertNotNull($history->changed_at);
    }

    public function test_pesan_kosong_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, '')->assertSessionHasErrors(['message' => 'Pesan lamaran wajib diisi.']);
        $this->kirim($user, $project, '      ')->assertSessionHasErrors('message');

        $this->assertSame(0, Application::count());
    }

    public function test_pesan_kurang_dari_50_karakter_ditolak_dan_tepat_50_diterima(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, str_repeat('a', 49))
            ->assertSessionHasErrors(['message' => 'Pesan lamaran minimal 50 karakter.']);
        $this->assertSame(0, Application::count());

        $this->kirim($user, $project, str_repeat('a', 50))->assertSessionHasNoErrors();
        $this->assertSame(1, Application::count());
    }

    public function test_proyek_yang_sudah_ditutup_tidak_menerima_lamaran(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject(['status' => ProjectStatus::Closed]);

        $this->kirim($user, $project, $this->validMessage())
            ->assertRedirect(route('kandidat.proyek.show', $project))
            ->assertSessionHas('error', 'Proyek ini sudah ditutup dan tidak lagi menerima lamaran.');

        $this->assertSame(0, Application::count());
    }

    public function test_lamaran_setelah_tenggat_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject(['application_deadline' => now()->subDay()->toDateString()]);

        $this->kirim($user, $project, $this->validMessage())
            ->assertSessionHas('error', 'Tenggat lamaran proyek ini sudah berakhir.');

        $this->assertSame(0, Application::count());
    }

    public function test_pada_hari_tenggat_lamaran_masih_diterima(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject(['application_deadline' => now()->toDateString()]);

        $this->kirim($user, $project, $this->validMessage())->assertSessionHas('success');
        $this->assertSame(1, Application::count());
    }

    public function test_lamaran_kedua_pada_proyek_yang_sama_ditolak(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage())->assertSessionHas('success');
        $this->kirim($user, $project, $this->validMessage() . ' Pesan kedua.')
            ->assertSessionHas('error');

        $this->assertSame(1, Application::count());
        $this->assertSame(1, ApplicationStatusHistory::count());
    }

    public function test_kandidat_berbeda_boleh_melamar_proyek_yang_sama(): void
    {
        $project = $this->makeProject();

        $this->kirim($this->makeKandidat(), $project, $this->validMessage());
        $this->kirim($this->makeKandidat(), $project, $this->validMessage());

        $this->assertSame(2, Application::count());
    }

    public function test_pengguna_bukan_kandidat_tidak_boleh_melamar(): void
    {
        $project = $this->makeProject();

        $this->kirim($this->makeUmkm(), $project, $this->validMessage())->assertForbidden();
        $this->kirim(\App\Models\User::factory()->admin()->create(), $project, $this->validMessage())->assertForbidden();

        $this->assertSame(0, Application::count());
    }

    public function test_kandidat_tanpa_keterampilan_tidak_memenuhi_syarat(): void
    {
        $user = $this->makeKandidat(skills: 0);
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage())
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'belum memenuhi syarat') && str_contains($m, 'minimal 1 keterampilan'));

        $this->assertSame(0, Application::count());
    }

    public function test_kandidat_dengan_data_pribadi_tidak_lengkap_tidak_memenuhi_syarat(): void
    {
        $user = $this->makeKandidat(['city' => '']);
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage())
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'data pribadi & akademik'));

        $this->assertSame(0, Application::count());
    }

    public function test_kandidat_tanpa_profil_tidak_bisa_melamar(): void
    {
        $user = \App\Models\User::factory()->kandidat()->create();
        $project = $this->makeProject();

        $this->kirim($user, $project, $this->validMessage())->assertSessionHas('error');
        $this->assertSame(0, Application::count());
    }

    public function test_pengalaman_dan_portofolio_tidak_wajib(): void
    {
        $user = $this->makeKandidat(experiences: 0, portfolios: 0);

        $this->kirim($user, $this->makeProject(), $this->validMessage())->assertSessionHas('success');
        $this->assertSame(1, Application::count());
    }

    public function test_proyek_tidak_ditemukan(): void
    {
        $this->actingAs($this->makeKandidat())
            ->post(route('kandidat.proyek.lamaran.store', 9999), ['message' => $this->validMessage()])
            ->assertNotFound();
    }

    public function test_proyek_draf_tidak_bisa_dilamar_dan_tidak_terlihat(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject(['status' => ProjectStatus::Draft]);

        $this->kirim($user, $project, $this->validMessage())->assertNotFound();
        $this->assertSame(0, Application::count());
    }

    /**
     * Mensimulasikan dua request bersamaan: tepat sebelum lamaran disimpan, "request lain" menyimpan lamaran
     * yang sama. Unique index database harus menolak insert kedua, dan service mengubahnya menjadi pesan yang jelas
     * tanpa meninggalkan riwayat status yatim. (Insert "request lain" ikut di-rollback karena memakai koneksi
     * yang sama dengan test ini, jadi yang diperiksa adalah perilaku service, bukan jumlah baris akhir.)
     */
    public function test_lamaran_ganda_akibat_request_bersamaan_dicegah_oleh_unique_index(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();
        $profileId = $user->candidateProfile->id;

        Application::creating(function () use ($project, $profileId) {
            DB::table('applications')->insert([
                'project_id' => $project->id, 'candidate_profile_id' => $profileId,
                'message' => 'request lain', 'status' => 'pending',
            ]);
        });

        try {
            app(ApplicationService::class)->submit($user, $project, $this->validMessage());
            $this->fail('Lamaran ganda seharusnya ditolak.');
        } catch (ApplicationNotAllowedException $e) {
            $this->assertStringContainsString('sudah pernah melamar', $e->getMessage());
        } finally {
            Application::flushEventListeners();
        }

        $this->assertSame(0, ApplicationStatusHistory::count());
    }

    public function test_unique_index_database_menolak_lamaran_ganda_langsung(): void
    {
        $user = $this->makeKandidat();
        $project = $this->makeProject();
        $row = ['project_id' => $project->id, 'candidate_profile_id' => $user->candidateProfile->id, 'message' => 'x', 'status' => 'pending'];

        DB::table('applications')->insert($row);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        DB::table('applications')->insert($row);
    }
}
