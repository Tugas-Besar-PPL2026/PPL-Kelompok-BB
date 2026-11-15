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
 * Pengujian PBI-20: Masukkan ke Terpilih Sementara dan Tolak dengan Catatan (FR-12).
 */
class CandidateDecisionTest extends TestCase
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

    public function test_umkm_can_shortlist_without_note_and_history_is_saved(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist'])
            ->assertRedirect(route('umkm.seleksi.show', $application))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::Shortlisted, $application->status);

        $history = $application->statusHistories()->first();
        $this->assertSame(ApplicationStatus::Shortlisted, $history->status);
        $this->assertNull($history->note);
        $this->assertNotNull($history->changed_at);
    }

    public function test_shortlist_with_note_saves_note_on_application_and_history(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist', 'catatan' => 'Portofolio kuat'])
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('Portofolio kuat', $application->selection_note);
        $this->assertSame('Portofolio kuat', $application->statusHistories()->first()->note);
    }

    public function test_reject_requires_a_note_and_does_not_change_status_when_missing(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        foreach ([null, '', '   '] as $note) {
            $this->actingAs($this->umkm)
                ->from(route('umkm.seleksi.show', $application))
                ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'reject', 'catatan' => $note])
                ->assertSessionHasErrors(['catatan' => 'Catatan penolakan wajib diisi agar kandidat mengetahui alasannya.']);
        }

        $this->assertSame(ApplicationStatus::Pending, $application->refresh()->status);
        $this->assertSame(0, $application->statusHistories()->count());
    }

    public function test_reject_with_note_saves_status_note_and_history(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Shortlisted);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'reject', 'catatan' => 'Belum sesuai kebutuhan'])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::Rejected, $application->status);
        $this->assertSame('Belum sesuai kebutuhan', $application->selection_note);

        $history = $application->statusHistories()->first();
        $this->assertSame(ApplicationStatus::Rejected, $history->status);
        $this->assertSame('Belum sesuai kebutuhan', $history->note);
        $this->assertNotNull($history->changed_at);
    }

    public function test_accepted_application_cannot_be_rejected_or_shortlisted(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Accepted);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'reject', 'catatan' => 'Berubah pikiran'])
            ->assertSessionHas('error');
        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist'])
            ->assertSessionHas('error');

        $this->assertSame(ApplicationStatus::Accepted, $application->refresh()->status);
        $this->assertSame(0, $application->statusHistories()->count());
    }

    public function test_rejected_and_cancelled_applications_cannot_be_processed_again(): void
    {
        foreach ([ApplicationStatus::Rejected, ApplicationStatus::Cancelled] as $status) {
            $application = $this->apply($this->project, $status);

            $this->actingAs($this->umkm)
                ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist'])
                ->assertSessionHas('error');

            $this->assertSame($status, $application->refresh()->status);
        }
    }

    public function test_invalid_decision_value_is_rejected(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'hapus'])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame(ApplicationStatus::Pending, $application->refresh()->status);
    }

    public function test_other_umkm_cannot_process_the_application(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);
        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');

        $this->actingAs($other)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist'])
            ->assertForbidden();

        $this->assertSame(ApplicationStatus::Pending, $application->refresh()->status);
    }

    public function test_candidate_cannot_process_the_application(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);
        $kandidat = $application->candidateProfile->user;

        $this->actingAs($kandidat)
            ->post(route('umkm.seleksi.keputusan', $application), ['keputusan' => 'shortlist'])
            ->assertForbidden();

        $this->assertSame(ApplicationStatus::Pending, $application->refresh()->status);
    }

    public function test_decision_does_not_consume_quota(): void
    {
        $quotaOne = $this->makeProject($this->umkm, 1);
        $first = $this->apply($quotaOne, ApplicationStatus::Pending);
        $second = $this->apply($quotaOne, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $first), ['keputusan' => 'shortlist']);
        $this->actingAs($this->umkm)->post(route('umkm.seleksi.keputusan', $second), ['keputusan' => 'reject', 'catatan' => 'Tidak cocok']);

        $this->assertSame(0, Application::where('project_id', $quotaOne->id)->where('status', ApplicationStatus::Accepted->value)->count());
    }

    public function test_detail_page_shows_live_decision_form(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertSee('Simpan Keputusan')
            ->assertSee('name="keputusan"', false)
            ->assertSee(route('umkm.seleksi.keputusan', $application), false);
    }

    public function test_detail_page_of_accepted_application_has_no_decision_form(): void
    {
        $application = $this->apply($this->project, ApplicationStatus::Accepted);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertDontSee('route-never', false)
            ->assertSee('tidak dapat diubah lagi');
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