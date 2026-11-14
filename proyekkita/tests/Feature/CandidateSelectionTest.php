<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian PBI-19: UMKM melihat daftar pelamar dan detail pelamar (FR-11).
 */
class CandidateSelectionTest extends TestCase
{
    use RefreshDatabase;

    private User $umkm;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkm = $this->makeUmkm('umkm@test.id', 'Kopi Nusantara');
        $this->project = $this->makeProject($this->umkm, 'Redesain Katalog', 2);
    }

    // ---------- Daftar pelamar ----------

    public function test_umkm_can_open_applicant_list_with_correct_statistics_and_quota(): void
    {
        $this->apply($this->project, 'Fajar Pratama', ApplicationStatus::Accepted);
        $this->apply($this->project, 'Nadia Putri', ApplicationStatus::Shortlisted);
        $this->apply($this->project, 'Raka Aditya', ApplicationStatus::Pending);
        $this->apply($this->project, 'Salsabila Nur', ApplicationStatus::Pending);
        $this->apply($this->project, 'Dimas Prakoso', ApplicationStatus::Rejected);

        $response = $this->actingAs($this->umkm)->get(route('umkm.seleksi.index'));

        $response->assertOk()
            ->assertSee('Seleksi Kandidat')
            ->assertSee('Fajar Pratama')->assertSee('Dimas Prakoso')
            ->assertSee('Kuota kandidat terisi 1 dari 2')
            ->assertSee('Sisa 1 kandidat dapat diterima');

        $response->assertViewHas('counts', [
            'all' => 5, 'pending' => 2, 'shortlisted' => 1, 'accepted' => 1, 'rejected' => 1,
        ]);
        // Terpilih Sementara tidak dihitung sebagai kuota terisi
        $response->assertViewHas('quota', fn ($q) => $q['accepted'] === 1 && $q['total'] === 2 && $q['remaining'] === 1 && $q['percent'] === 50);
    }

    public function test_status_tab_filters_the_list(): void
    {
        $this->apply($this->project, 'Fajar Pratama', ApplicationStatus::Accepted);
        $this->apply($this->project, 'Raka Aditya', ApplicationStatus::Pending);

        $this->actingAs($this->umkm)
            ->get(route('umkm.seleksi.index', ['proyek' => $this->project->id, 'status' => 'pending']))
            ->assertOk()
            ->assertSee('Raka Aditya')
            ->assertDontSee('Fajar Pratama');
    }

    public function test_unknown_status_value_falls_back_to_all(): void
    {
        $this->apply($this->project, 'Fajar Pratama', ApplicationStatus::Accepted);

        $this->actingAs($this->umkm)
            ->get(route('umkm.seleksi.index', ['status' => 'bukan-status']))
            ->assertOk()
            ->assertSee('Fajar Pratama');
    }

    public function test_cancelled_applications_are_not_counted_or_listed(): void
    {
        $this->apply($this->project, 'Fajar Pratama', ApplicationStatus::Pending);
        $this->apply($this->project, 'Batal Mundur', ApplicationStatus::Cancelled);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.index'))
            ->assertOk()
            ->assertDontSee('Batal Mundur')
            ->assertViewHas('counts', fn ($c) => $c['all'] === 1);
    }

    public function test_project_dropdown_switches_data_and_only_lists_own_projects(): void
    {
        $second = $this->makeProject($this->umkm, 'Poster Promo', 1, publishedAt: now()->subDay());
        $this->apply($this->project, 'Pelamar Proyek Satu', ApplicationStatus::Pending);
        $this->apply($second, 'Pelamar Proyek Dua', ApplicationStatus::Pending);

        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');
        $this->makeProject($other, 'Proyek Rahasia Toko Lain', 1);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.index', ['proyek' => $second->id]))
            ->assertOk()
            ->assertSee('Pelamar Proyek Dua')
            ->assertDontSee('Pelamar Proyek Satu')
            ->assertSee('Poster Promo')
            ->assertDontSee('Proyek Rahasia Toko Lain');
    }

    public function test_umkm_cannot_open_applicants_of_another_umkms_project(): void
    {
        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');
        $foreign = $this->makeProject($other, 'Proyek Toko Lain', 1);
        $this->apply($foreign, 'Pelamar Asing', ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.index', ['proyek' => $foreign->id]))
            ->assertForbidden();
    }

    public function test_candidate_cannot_open_umkm_pages(): void
    {
        $application = $this->apply($this->project, 'Fajar Pratama', ApplicationStatus::Pending);
        $candidate = $application->candidateProfile->user;

        $this->actingAs($candidate)->get(route('umkm.seleksi.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('umkm.seleksi.show', $application))->assertForbidden();
    }

    public function test_project_without_applicants_shows_empty_state_without_error(): void
    {
        $this->actingAs($this->umkm)->get(route('umkm.seleksi.index'))
            ->assertOk()
            ->assertSee('Belum ada pelamar pada proyek ini')
            ->assertSee('Kuota kandidat terisi 0 dari 2');
    }

    public function test_umkm_without_published_project_sees_empty_state(): void
    {
        $newcomer = $this->makeUmkm('baru@test.id', 'Usaha Baru');

        $this->actingAs($newcomer)->get(route('umkm.seleksi.index'))
            ->assertOk()
            ->assertSee('Belum ada proyek yang menerima pelamar');
    }

    // ---------- Detail pelamar ----------

    public function test_detail_shows_profile_message_skills_experience_portfolio_rating_and_history(): void
    {
        $required = Skill::create(['name' => 'Figma']);
        $extra = Skill::create(['name' => 'Ilustrasi']);
        $this->project->skills()->attach($required->id);

        $application = $this->apply($this->project, 'Nadia Putri Rahma', ApplicationStatus::Shortlisted, 'Halo! Saya tertarik dengan proyek ini.');
        $profile = $application->candidateProfile;
        $profile->skills()->attach([$required->id, $extra->id]);
        $profile->experiences()->create(['work_type' => 'Ilustrasi kemasan makanan', 'duration' => '6 bulan', 'description' => 'Membuat 5 kemasan keripik.']);
        $profile->portfolios()->create(['title' => 'Label Sambal', 'description' => 'Label botol', 'link' => 'behance.net/nadia/sambal']);
        $this->giveRating($profile, [5, 4]);

        $application->statusHistories()->create(['status' => ApplicationStatus::Pending, 'changed_at' => now()->subDays(2)]);
        $application->statusHistories()->create(['status' => ApplicationStatus::Shortlisted, 'note' => 'Portofolio kuat', 'changed_at' => now()->subDay()]);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertSee('Detail Pelamar')
            ->assertSee('Nadia Putri Rahma')
            ->assertSee('Terpilih Sementara')
            ->assertSee('Halo! Saya tertarik dengan proyek ini.')
            ->assertSee('Ilustrasi kemasan makanan')
            ->assertSee('Membuat 5 kemasan keripik.')
            ->assertSee('Label Sambal')
            ->assertSee('href="https://behance.net/nadia/sambal"', false)
            ->assertSee('4,5')
            ->assertSee('rata-rata dari 2 evaluasi UMKM')
            ->assertSee('2 proyek selesai')
            ->assertSee('<span class="tag req">Figma</span>', false)
            ->assertSee('<span class="tag oth">Ilustrasi</span>', false)
            ->assertSee('Lamaran diterima sistem')
            ->assertSee('Catatan: Portofolio kuat');
    }

    public function test_detail_does_not_make_unsafe_portfolio_links_clickable(): void
    {
        $application = $this->apply($this->project, 'Kandidat Nakal', ApplicationStatus::Pending);
        $application->candidateProfile->portfolios()->create(['title' => 'Karya', 'description' => 'x', 'link' => 'javascript:alert(1)']);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertDontSee('href="javascript:', false);
    }

    public function test_umkm_can_open_applicant_portfolio_document_and_others_cannot(): void
    {
        Storage::fake('public');
        $application = $this->apply($this->project, 'Nadia Putri', ApplicationStatus::Pending);
        Storage::disk('public')->put('portfolios/1/cv.pdf', '%PDF-1.4 demo');
        $portfolio = $application->candidateProfile->portfolios()->create(['title' => 'CV', 'description' => 'x', 'file_path' => 'portfolios/1/cv.pdf']);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertSee(route('umkm.seleksi.portofolio', [$application, $portfolio]), false)
            ->assertSee('cv.pdf');

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.portofolio', [$application, $portfolio]))->assertOk();

        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');
        $this->actingAs($other)->get(route('umkm.seleksi.portofolio', [$application, $portfolio]))->assertForbidden();
    }

    public function test_portfolio_document_of_another_candidate_cannot_be_read_through_this_application(): void
    {
        Storage::fake('public');
        $mine = $this->apply($this->project, 'Kandidat A', ApplicationStatus::Pending);
        $theirs = $this->apply($this->project, 'Kandidat B', ApplicationStatus::Pending);
        Storage::disk('public')->put('portfolios/9/b.pdf', '%PDF-1.4 demo');
        $portfolio = $theirs->candidateProfile->portfolios()->create(['title' => 'B', 'description' => 'x', 'file_path' => 'portfolios/9/b.pdf']);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.portofolio', [$mine, $portfolio]))->assertNotFound();
    }

    public function test_missing_portfolio_file_and_empty_portfolio_show_clear_messages(): void
    {
        Storage::fake('public');
        $application = $this->apply($this->project, 'Kandidat Kosong', ApplicationStatus::Pending);
        $profile = $application->candidateProfile;
        $missing = $profile->portfolios()->create(['title' => 'Hilang', 'description' => 'x', 'file_path' => 'portfolios/1/hilang.pdf']);
        $profile->portfolios()->create(['title' => 'Kosong', 'description' => 'x']);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertSee('berkas tidak ditemukan')
            ->assertSee('Tidak ada tautan atau dokumen.');

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.portofolio', [$application, $missing]))->assertNotFound();
    }

    public function test_detail_handles_candidate_without_experience_portfolio_rating_or_history(): void
    {
        $application = $this->apply($this->project, 'Kandidat Baru', ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))
            ->assertOk()
            ->assertSee('Belum ada penilaian')
            ->assertSee('Kandidat belum menambahkan keterampilan.')
            ->assertSee('Kandidat belum menambahkan pengalaman kerja.')
            ->assertSee('Kandidat belum menambahkan portofolio.')
            ->assertSee('Belum ada riwayat perubahan status');
    }

    public function test_detail_of_another_umkms_application_is_forbidden(): void
    {
        $other = $this->makeUmkm('lain@test.id', 'Toko Lain');
        $foreign = $this->makeProject($other, 'Proyek Toko Lain', 1);
        $application = $this->apply($foreign, 'Pelamar Asing', ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))->assertForbidden();
    }

    public function test_detail_page_does_not_change_application_status(): void
    {
        $application = $this->apply($this->project, 'Nadia Putri', ApplicationStatus::Pending);

        $this->actingAs($this->umkm)->get(route('umkm.seleksi.show', $application))->assertOk();

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }

    public function test_detail_route_rejects_non_numeric_parameter(): void
    {
        $this->actingAs($this->umkm)->get('/umkm/seleksi/pelamar/abc')->assertNotFound();
    }

    // ---------- Pembantu ----------

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

    private function makeProject(User $umkm, string $title, int $quota, ?\DateTimeInterface $publishedAt = null): Project
    {
        $category = Category::firstOrCreate(['name' => 'Desain'], ['is_active' => true]);

        return $umkm->umkmProfile->projects()->create([
            'category_id' => $category->id, 'title' => $title, 'description' => 'Deskripsi', 'expected_output' => 'Hasil',
            'application_deadline' => now()->addDays(10)->toDateString(), 'target_completion_date' => now()->addDays(30)->toDateString(),
            'quota' => $quota, 'total_compensation' => 1000000, 'status' => ProjectStatus::Published,
            'published_at' => $publishedAt ?? now(),
        ]);
    }

    private function apply(Project $project, string $name, ApplicationStatus $status, string $message = 'Saya tertarik.'): Application
    {
        static $n = 0;
        $n++;

        $user = User::create(['name' => $name, 'email' => "kandidat{$n}@test.id", 'password' => 'password', 'role' => UserRole::Kandidat]);
        $profile = $user->candidateProfile()->create([
            'full_name' => $name, 'headline' => 'Desainer', 'university' => 'Universitas Demo',
            'major' => 'DKV', 'city' => 'Kota Bandung', 'summary' => 'Ringkasan diri '.$name,
        ]);

        return Application::create([
            'project_id' => $project->id, 'candidate_profile_id' => $profile->id,
            'message' => $message, 'status' => $status,
        ]);
    }

    /** Membuat evaluasi UMKM untuk kandidat lewat kesepakatan yang sudah selesai. */
    private function giveRating($profile, array $ratings): void
    {
        foreach ($ratings as $i => $rating) {
            $past = $this->makeProject($this->umkm, 'Proyek Lama '.$profile->id.'-'.$i, 1);
            $app = Application::create(['project_id' => $past->id, 'candidate_profile_id' => $profile->id, 'message' => 'Siap.', 'status' => ApplicationStatus::Accepted]);
            $agreement = Agreement::create([
                'application_id' => $app->id, 'scope' => 's', 'deliverables' => 'd', 'deadline' => now()->toDateString(),
                'total_compensation' => 1000000, 'revision_limit' => 2, 'status' => AgreementStatus::Completed,
            ]);
            Evaluation::create(['agreement_id' => $agreement->id, 'reviewer_id' => $this->umkm->id, 'reviewee_id' => $profile->user_id, 'rating' => $rating]);
        }
    }
}
