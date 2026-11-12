<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Application;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian PBI-21: Terima Pelamar dengan validasi kuota (FR-13).
 */
class CandidateAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $umkm;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkm = $this->makeUmkm('umkm@test.id', 'Kopi Nusantara');
        $this->project = $this->makeProject($this->umkm, 3);
    }

    public function test_umkm_can_accept_pending_applicant_and_history_is_saved(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept', 'catatan' => 'Selamat bergabung'])
            ->assertRedirect(route('umkm.seleksi.show', $application))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::Accepted, $application->status);
        $this->assertSame('Selamat bergabung', $application->selection_note);

        $history = $application->statusHistories()->first();
        $this->assertSame(ApplicationStatus::Accepted, $history->status);
        $this->assertNotNull($history->changed_at);
    }

    public function test_umkm_can_accept_a_shortlisted_applicant_without_note(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Shortlisted);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApplicationStatus::Accepted, $application->refresh()->status);
    }

    public function test_accept_is_refused_with_exact_message_when_quota_is_full(): void
    {
        $this->apply($this->project, ApplicationStatus::Accepted);
        $this->apply($this->project, ApplicationStatus::Accepted);
        $this->apply($this->project, ApplicationStatus::Accepted);
        $extra = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $extra), ['keputusan' => 'accept'])
            ->assertSessionHas('error', 'Kuota kandidat untuk proyek ini sudah terpenuhi. Kandidat tidak dapat diterima lagi.');

        $this->assertSame(ApplicationStatus::Pending, $extra->refresh()->status);
        $this->assertSame(0, $extra->statusHistories()->count());
        $this->assertSame(3, $this->acceptedCount($this->project));
    }

    public function test_only_accepted_applications_count_toward_quota(): void
    {
        // Terpilih Sementara, Menunggu, Ditolak, dan Dibatalkan tidak menghabiskan kuota.
        $this->apply($this->project, ApplicationStatus::Shortlisted);
        $this->apply($this->project, ApplicationStatus::Shortlisted);
        $this->apply($this->project, ApplicationStatus::Rejected);
        $this->apply($this->project, ApplicationStatus::Cancelled);
        $candidate = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $candidate), ['keputusan' => 'accept'])
            ->assertSessionHas('success');

        $this->assertSame(1, $this->acceptedCount($this->project));
    }

    public function test_quota_is_counted_per_project(): void
    {
        $other = $this->makeProject($this->umkm, 1);
        $this->apply($other, ApplicationStatus::Accepted);
        $candidate = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $candidate), ['keputusan' => 'accept'])
            ->assertSessionHas('success');
    }

    public function test_last_slot_can_be_filled_and_the_next_one_is_refused(): void
    {
        $project = $this->makeProject($this->umkm, 1);
        $first = $this->apply($project, ApplicationStatus::Pending);
        $second = $this->apply($project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $first), ['keputusan' => 'accept'])->assertSessionHas('success');
        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $second), ['keputusan' => 'accept'])
            ->assertSessionHas('error', 'Kuota kandidat untuk proyek ini sudah terpenuhi. Kandidat tidak dapat diterima lagi.');

        $this->assertSame(1, $this->acceptedCount($project));
    }

    public function test_accepting_the_same_applicant_twice_does_not_add_history_or_quota(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept']);
        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept'])
            ->assertSessionHas('error');

        $this->assertSame(1, $application->statusHistories()->count());
        $this->assertSame(1, $this->acceptedCount($this->project));
    }

    public function test_rejected_or_cancelled_applicant_cannot_be_accepted(): void
    {
        foreach ([ApplicationStatus::Rejected, ApplicationStatus::Cancelled] as $status) {
            $application = $this->apply($this->project, $status);

            $this->actingAs($this->umkm)
                ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept'])
                ->assertSessionHas('error');

            $this->assertSame($status, $application->refresh()->status);
        }
    }

    public function test_other_umkm_cannot_accept_the_applicant(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);
        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');

        $this->actingAs($other)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept'])
            ->assertForbidden();

        $this->assertSame(ApplicationStatus::Pending, $application->refresh()->status);
    }

    public function test_accepting_does_not_create_an_agreement(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'accept']);

        $this->assertNull($application->refresh()->agreement);
    }

    public function test_list_page_updates_quota_stat_and_tab_after_acceptance(): void
    {
        $a = $this->apply($this->project, ApplicationStatus::Pending);
        $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $a), ['keputusan' => 'accept']);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.index'))
            ->assertOk()
            ->assertSee('Kuota kandidat terisi 1 dari 3')
            ->assertViewHas('counts', ['all' => 2, 'pending' => 1, 'shortlisted' => 0, 'accepted' => 1, 'rejected' => 0]);
    }

    public function test_detail_page_disables_accept_option_when_quota_is_full(): void
    {
        $project = $this->makeProject($this->umkm, 1);
        $this->apply($project, ApplicationStatus::Accepted);
        $pending = $this->apply($project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $pending))
            ->assertOk()
            ->assertSee('Kuota sudah terpenuhi');
    }

    private function acceptedCount(Project $project): int
    {
        return Application::where('project_id', $project->id)->where('status', ApplicationStatus::Accepted->value)->count();
    }

    private function makeUmkm(string $email, string $business): User
    {
        $user = User::create(['name' => $business.' Owner', 'email' => $email, 'password' => 'password', 'role' => UserRole::Umkm]);
        $user->umkmProfile()->create([
            'business_name' => $business, 'description' => 'Usaha demo', 'business_field' => 'Kuliner',
            'contact_phone' => '0812', 'city' => 'Bandung', 'address' => 'Jl. Demo 1',
            'verification_status' => VerificationStatus::Verified,
        ]);

        return $user;
    }

    private function makeProject(User $umkm, int $quota): Project
    {
        $category = Category::firstOrCreate(['name' => 'Desain'], ['is_active' => true]);

        return $umkm->umkmProfile->projects()->create([
            'category_id' => $category->id, 'title' => 'Proyek '.uniqid(), 'description' => 'Deskripsi', 'expected_output' => 'Hasil',
            'application_deadline' => now()->addDays(10)->toDateString(), 'target_completion_date' => now()->addDays(30)->toDateString(),
            'quota' => $quota, 'total_compensation' => 1000000, 'status' => ProjectStatus::Published, 'published_at' => now(),
        ]);
    }

    private function apply(Project $project, ApplicationStatus $status): Application
    {
        static $n = 0;
        $n++;

        $user = User::create(['name' => "Kandidat {$n}", 'email' => "dec{$n}@test.id", 'password' => 'password', 'role' => UserRole::Kandidat]);
        $profile = $user->candidateProfile()->create([
            'full_name' => "Kandidat {$n}", 'headline' => 'Desainer', 'university' => 'Universitas Demo',
            'major' => 'DKV', 'city' => 'Bandung', 'summary' => 'Ringkasan',
        ]);

        return Application::create([
            'project_id' => $project->id, 'candidate_profile_id' => $profile->id,
            'message' => 'Saya tertarik.', 'status' => $status,
        ]);
    }
}
