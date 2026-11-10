<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\Skill;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsesInMemoryDatabase;
use Tests\TestCase;

/**
 * PBI-19: daftar pelamar dan detail pelamar untuk UMKM (FR-11).
 */
class CandidateSelectionTest extends TestCase
{
    use RefreshDatabase, UsesInMemoryDatabase;

    private int $seq = 0;

    // ---------------------------------------------------------------- helpers

    private function umkm(string $name = 'Kopi Nusantara'): UmkmProfile
    {
        $user = User::factory()->umkm()->create();

        return $user->umkmProfile()->create([
            'business_name' => $name, 'description' => 'Usaha', 'business_field' => 'Kuliner',
            'contact_phone' => '0812', 'city' => 'Bandung', 'address' => 'Jl. A',
            'verification_status' => VerificationStatus::Verified,
        ]);
    }

    private function project(UmkmProfile $umkm, array $attrs = []): Project
    {
        $category = Category::firstOrCreate(['name' => 'Desain'], ['is_active' => true]);

        return Project::create($attrs + [
            'umkm_profile_id' => $umkm->id, 'category_id' => $category->id, 'title' => 'Proyek '.++$this->seq,
            'description' => 'Deskripsi', 'expected_output' => 'Hasil', 'application_deadline' => now()->addDays(10),
            'target_completion_date' => now()->addDays(30), 'quota' => 2, 'total_compensation' => 1000000,
            'status' => ProjectStatus::Published, 'published_at' => '2026-11-03 09:00:00',
        ]);
    }

    private function candidate(string $name = null, array $profile = []): CandidateProfile
    {
        $name ??= 'Kandidat '.++$this->seq;
        $user = User::factory()->kandidat()->create(['name' => $name]);

        return $user->candidateProfile()->create($profile + [
            'full_name' => $name, 'headline' => 'Desainer', 'university' => 'Universitas X',
            'major' => 'DKV', 'city' => 'Kota Bandung', 'summary' => 'Ringkasan '.$name,
        ]);
    }

    private function apply(Project $project, CandidateProfile $candidate, ApplicationStatus $status = ApplicationStatus::Pending, ?string $message = 'Saya tertarik.'): Application
    {
        return Application::create([
            'project_id' => $project->id, 'candidate_profile_id' => $candidate->id,
            'message' => $message ?? '', 'status' => $status,
        ]);
    }

    private function skill(string $name): Skill
    {
        return Skill::firstOrCreate(['name' => $name], ['is_active' => true]);
    }

    private function completedJob(CandidateProfile $candidate, int $rating): void
    {
        $client = $this->umkm('Klien '.++$this->seq);
        $application = $this->apply($this->project($client, ['status' => ProjectStatus::Closed]), $candidate, ApplicationStatus::Accepted);
        $agreement = Agreement::create([
            'application_id' => $application->id, 'scope' => 's', 'deliverables' => 'd', 'deadline' => '2026-09-01',
            'total_compensation' => 1, 'revision_limit' => 2, 'status' => AgreementStatus::Completed,
        ]);
        Evaluation::create(['agreement_id' => $agreement->id, 'reviewer_id' => $client->user_id, 'reviewee_id' => $candidate->user_id, 'rating' => $rating]);
    }

    private function index(UmkmProfile $umkm, array $query = [])
    {
        return $this->actingAs($umkm->user)->get(route('umkm.seleksi.index', $query));
    }

    // ------------------------------------------------------- daftar pelamar

    public function test_umkm_can_open_applicant_list_with_stats_quota_and_rows(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm, ['quota' => 2]);
        $this->apply($project, $this->candidate('Fajar Pratama'), ApplicationStatus::Accepted);
        $this->apply($project, $this->candidate('Nadia Putri Rahma'), ApplicationStatus::Shortlisted);
        $this->apply($project, $this->candidate('Raka Aditya'));
        $this->apply($project, $this->candidate('Salsabila Nur'));
        $this->apply($project, $this->candidate('Dimas Prakoso'), ApplicationStatus::Rejected);

        $response = $this->index($umkm)->assertOk()
            ->assertSee('Seleksi Kandidat')
            ->assertSee('Tinjau pelamar, masukkan ke daftar terpilih sementara, lalu terima atau tolak sesuai kuota proyek.')
            ->assertSee('Kuota kandidat terisi 1 dari 2')
            ->assertSee('Sisa 1 kandidat dapat diterima')
            ->assertSee('sejak 3 Nov 2026')
            ->assertSee('1 / 2');

        $this->assertSame(
            ['all' => 5, 'pending' => 2, 'shortlisted' => 1, 'accepted' => 1, 'rejected' => 1],
            $response->viewData('counts'),
        );

        // Urutan: Diterima, Terpilih Sementara, Menunggu Keputusan, Ditolak
        $response->assertSeeInOrder(['Fajar Pratama', 'Nadia Putri Rahma', 'Raka Aditya', 'Salsabila Nur', 'Dimas Prakoso']);
        foreach (['Diterima', 'Terpilih Sementara', 'Menunggu Keputusan', 'Ditolak'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_quota_counts_only_accepted_not_shortlisted(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm, ['quota' => 3]);
        $this->apply($project, $this->candidate(), ApplicationStatus::Shortlisted);
        $this->apply($project, $this->candidate(), ApplicationStatus::Shortlisted);

        $quota = $this->index($umkm)->assertOk()->viewData('quota');

        $this->assertSame(['accepted' => 0, 'total' => 3, 'percent' => 0, 'remaining' => 3, 'full' => false], $quota);
    }

    public function test_full_quota_shows_progress_100_and_no_remaining(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm, ['quota' => 1]);
        $this->apply($project, $this->candidate(), ApplicationStatus::Accepted);

        $this->index($umkm)->assertOk()->assertSee('Kuota kandidat terisi 1 dari 1')->assertSee('Kuota terpenuhi')
            ->assertSee('width:100%', false);
    }

    public function test_list_only_shows_applicants_of_own_projects(): void
    {
        $mine = $this->umkm('Milik Saya');
        $other = $this->umkm('Milik Orang Lain');
        $this->apply($this->project($mine), $this->candidate('Pelamar Saya'));
        $this->apply($this->project($other, ['title' => 'Proyek Rahasia']), $this->candidate('Pelamar Orang Lain'));

        $this->index($mine)->assertOk()
            ->assertSee('Pelamar Saya')
            ->assertDontSee('Pelamar Orang Lain')
            ->assertDontSee('Proyek Rahasia');
    }

    public function test_project_dropdown_lists_own_projects_and_switches_data(): void
    {
        $umkm = $this->umkm();
        $first = $this->project($umkm, ['title' => 'Proyek Lama', 'published_at' => '2026-10-01 08:00:00']);
        $second = $this->project($umkm, ['title' => 'Proyek Baru', 'published_at' => '2026-11-03 08:00:00']);
        $this->project($umkm, ['title' => 'Masih Draf', 'status' => ProjectStatus::Draft, 'published_at' => null]);
        $this->project($this->umkm('Lain'), ['title' => 'Proyek Orang Lain']);
        $this->apply($first, $this->candidate('Pelamar Lama'));
        $this->apply($second, $this->candidate('Pelamar Baru'));

        // Bawaan: proyek terbaru
        $this->index($umkm)->assertOk()->assertSee('Pelamar Baru')->assertDontSee('Pelamar Lama')
            ->assertSee('Proyek Lama')->assertSee('Proyek Baru')
            ->assertDontSee('Masih Draf')->assertDontSee('Proyek Orang Lain');

        $this->index($umkm, ['proyek' => $first->id])->assertOk()->assertSee('Pelamar Lama')->assertDontSee('Pelamar Baru');
    }

    public function test_other_umkm_project_is_forbidden_and_invalid_ids_are_404(): void
    {
        $mine = $this->umkm();
        $this->project($mine);
        $foreign = $this->project($this->umkm('Lain'));

        $this->index($mine, ['proyek' => $foreign->id])->assertForbidden();
        $this->index($mine, ['proyek' => 999999])->assertNotFound();
        $this->index($mine, ['proyek' => 'abc'])->assertNotFound();
        $this->index($mine, ['proyek' => ['x']])->assertNotFound();
    }

    public function test_status_tabs_filter_and_counts(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        $this->apply($project, $this->candidate('A Diterima'), ApplicationStatus::Accepted);
        $this->apply($project, $this->candidate('B Menunggu'));
        $this->apply($project, $this->candidate('C Menunggu'));
        $this->apply($project, $this->candidate('D Ditolak'), ApplicationStatus::Rejected);

        $this->index($umkm, ['status' => 'pending'])->assertOk()
            ->assertSee('B Menunggu')->assertSee('C Menunggu')->assertDontSee('A Diterima')->assertDontSee('D Ditolak');
        $this->index($umkm, ['status' => 'accepted'])->assertSee('A Diterima')->assertDontSee('B Menunggu');
        $this->index($umkm, ['status' => 'rejected'])->assertSee('D Ditolak')->assertDontSee('A Diterima');

        $tabs = $this->index($umkm)->viewData('tabs');
        $this->assertSame(['Semua' => 4, 'Menunggu Keputusan' => 2, 'Terpilih Sementara' => 0, 'Diterima' => 1, 'Ditolak' => 1],
            collect($tabs)->pluck('count', 'label')->all());
    }

    public function test_unknown_status_falls_back_to_all_and_cancelled_applications_are_hidden(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        $this->apply($project, $this->candidate('Aktif'));
        $this->apply($project, $this->candidate('Membatalkan Diri'), ApplicationStatus::Cancelled);

        $this->index($umkm, ['status' => 'ngawur'])->assertOk()->assertSee('Aktif')->assertDontSee('Membatalkan Diri');
        $this->index($umkm, ['status' => 'cancelled'])->assertOk()->assertSee('Aktif');
        $this->assertSame(1, $this->index($umkm)->viewData('counts')['all']);
    }

    public function test_empty_states_do_not_error(): void
    {
        $umkm = $this->umkm();

        // Belum punya proyek
        $this->index($umkm)->assertOk()->assertSee('Belum ada proyek yang dapat menerima pelamar');

        // Proyek tanpa pelamar
        $project = $this->project($umkm, ['quota' => 4]);
        $this->index($umkm)->assertOk()->assertSee('Belum ada pelamar pada proyek ini')->assertSee('Kuota kandidat terisi 0 dari 4');

        // Tab kosong
        $this->apply($project, $this->candidate());
        $this->index($umkm, ['status' => 'accepted'])->assertOk()->assertSee('Tidak ada pelamar berstatus Diterima');
    }

    public function test_list_shows_skills_experience_rating_and_applied_date(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        $rated = $this->candidate('Si Berating', ['city' => 'Kab. Sumedang']);
        $rated->skills()->attach([$this->skill('Figma')->id, $this->skill('Canva')->id]);
        $rated->experiences()->create(['work_type' => 'A', 'duration' => '8 bulan', 'description' => 'x']);
        $rated->experiences()->create(['work_type' => 'B', 'duration' => '6 bulan', 'description' => 'y']);
        $this->completedJob($rated, 5);
        $this->completedJob($rated, 4);
        $this->apply($project, $rated);
        $this->apply($project, $this->candidate('Si Pemula'));

        $this->index($umkm)->assertOk()
            ->assertSee('Si Berating')->assertSee('Universitas X')->assertSee('Kab. Sumedang')
            ->assertSee('Figma')->assertSee('Canva')
            ->assertSee('2 pengalaman')->assertSee('1 thn 2 bln')
            ->assertSee('4,5')->assertSee('2 proyek selesai')
            ->assertSee('Belum ada penilaian')->assertSee('Belum ada pengalaman')->assertSee('Belum ada keterampilan')
            ->assertSee(now()->locale('id')->translatedFormat('j M Y'));
    }

    public function test_list_query_count_does_not_grow_with_applicants(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        $count = function () use ($umkm) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->index($umkm)->assertOk();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        foreach (range(1, 2) as $_) {
            $c = $this->candidate();
            $c->skills()->attach($this->skill('Figma')->id);
            $c->experiences()->create(['work_type' => 'A', 'duration' => '2 bulan', 'description' => 'x']);
            $this->apply($project, $c);
        }
        $count(); // pemanasan: request pertama ikut memuat user & profil UMKM untuk autentikasi
        $small = $count();

        foreach (range(1, 10) as $_) {
            $c = $this->candidate();
            $c->skills()->attach($this->skill('Figma')->id);
            $c->experiences()->create(['work_type' => 'A', 'duration' => '2 bulan', 'description' => 'x']);
            $this->apply($project, $c);
        }

        $this->assertSame($small, $count(), 'Jumlah query bertambah seiring jumlah pelamar (N+1).');
    }

    public function test_list_is_paginated_and_keeps_filters(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        foreach (range(1, 17) as $i) {
            $this->apply($project, $this->candidate(sprintf('Pelamar %02d', $i)));
        }

        $this->index($umkm, ['status' => 'pending'])->assertOk()->assertSee('Menampilkan 1–15 dari 17 pelamar')
            ->assertSee('page=2', false)->assertSee('status=pending', false);
        $this->index($umkm, ['status' => 'pending', 'page' => 2])->assertOk()->assertSee('Pelamar 16')->assertDontSee('Pelamar 01');
    }

    // ------------------------------------------------------ hak akses peran

    public function test_non_umkm_roles_cannot_access_selection_pages(): void
    {
        $umkm = $this->umkm();
        $application = $this->apply($this->project($umkm), $candidate = $this->candidate());
        $admin = User::factory()->admin()->create();

        foreach ([$candidate->user, $admin] as $user) {
            $this->actingAs($user)->get(route('umkm.seleksi.index'))->assertForbidden();
            $this->actingAs($user)->get(route('umkm.seleksi.show', $application))->assertForbidden();
        }
    }

    public function test_umkm_without_business_profile_is_blocked(): void
    {
        $user = User::factory()->umkm()->create();

        $this->actingAs($user)->get(route('umkm.seleksi.index'))->assertForbidden();
    }

    // ------------------------------------------------------ detail pelamar

    public function test_detail_shows_full_candidate_information(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm, ['title' => 'Redesain Label']);
        $project->skills()->attach($this->skill('Figma')->id);

        $candidate = $this->candidate('Nadia Putri Rahma', [
            'headline' => 'Ilustrator & desainer grafis', 'university' => 'Universitas Pendidikan Indonesia',
            'major' => 'S1 Desain Komunikasi Visual', 'city' => 'Kota Bandung', 'summary' => 'Mahasiswa DKV semester 7.',
        ]);
        $candidate->skills()->attach([$this->skill('Ilustrasi')->id, $this->skill('Figma')->id]);
        $candidate->experiences()->create(['work_type' => 'Ilustrasi kemasan', 'duration' => '6 bulan', 'description' => 'Membuat 5 kemasan.']);
        $candidate->portfolios()->create(['title' => 'Label Sambal', 'description' => 'Tiga varian.', 'link' => 'behance.net/nadia/sambal']);
        $this->completedJob($candidate, 5);
        $this->completedJob($candidate, 4);

        $application = $this->apply($project, $candidate, ApplicationStatus::Shortlisted, "Halo!\nSaya tertarik.");
        ApplicationStatusHistory::create(['application_id' => $application->id, 'status' => ApplicationStatus::Pending, 'changed_at' => '2026-11-04 20:11:00']);
        ApplicationStatusHistory::create(['application_id' => $application->id, 'status' => ApplicationStatus::Shortlisted, 'note' => 'Portofolio kuat', 'changed_at' => '2026-11-06 11:30:00']);

        $response = $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertSee('Detail Pelamar')->assertSee('Kembali ke Daftar')
            ->assertSee('Redesain Label')
            ->assertSee('Nadia Putri Rahma')->assertSee('Terpilih Sementara')
            ->assertSee('Ilustrator &amp; desainer grafis', false)
            ->assertSee('Universitas Pendidikan Indonesia')->assertSee('S1 Desain Komunikasi Visual')->assertSee('Kota Bandung')
            ->assertSee('4,5')->assertSee('rata-rata dari 2 evaluasi UMKM')->assertSee('2 proyek selesai')
            ->assertSee('Mahasiswa DKV semester 7.')
            ->assertSee('Saya tertarik.')->assertSee('Dikirim')
            ->assertSee('Ilustrasi kemasan')->assertSee('6 bulan')->assertSee('Membuat 5 kemasan.')
            ->assertSee('Label Sambal')->assertSee('Tiga varian.')
            ->assertSee('Keterampilan yang dibutuhkan proyek ditandai lebih tebal.')
            ->assertSee('<span class="tag req">Figma</span>', false)
            ->assertSee('<span class="tag ">Ilustrasi</span>', false)
            ->assertSee('href="https://behance.net/nadia/sambal"', false)
            ->assertSee('Keputusan Seleksi')->assertSee('Simpan Keputusan')->assertSee('Status saat ini')
            ->assertSee('Lamaran diterima sistem')->assertSee('Status awal: Menunggu Keputusan')
            ->assertSee('Catatan: Portofolio kuat')
            ->assertSee('6 Nov 2026, 11.30')->assertSee('4 Nov 2026, 20.11');

        // Timeline terbaru di atas
        $response->assertSeeInOrder(['6 Nov 2026, 11.30', '4 Nov 2026, 20.11']);
    }

    public function test_detail_panel_is_display_only_and_does_not_change_status(): void
    {
        $umkm = $this->umkm();
        $application = $this->apply($this->project($umkm), $this->candidate(), ApplicationStatus::Pending);

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertSee('Keputusan seleksi belum dapat diubah dari halaman ini.')
            ->assertDontSee('<form', false);

        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
        $this->assertSame(0, ApplicationStatusHistory::count());
    }

    public function test_detail_handles_candidate_without_optional_data(): void
    {
        $umkm = $this->umkm();
        $candidate = $this->candidate('Serba Kosong', ['summary' => '']);
        $application = $this->apply($this->project($umkm), $candidate, ApplicationStatus::Pending, null);

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertSee('Kandidat belum mengisi ringkasan diri.')
            ->assertSee('Kandidat tidak menyertakan pesan lamaran.')
            ->assertSee('Kandidat belum menambahkan keterampilan.')
            ->assertSee('Kandidat belum menambahkan pengalaman kerja.')
            ->assertSee('Kandidat belum menambahkan portofolio.')
            ->assertSee('Belum ada penilaian · 0 proyek selesai')
            ->assertSee('Belum ada riwayat status untuk lamaran ini.')
            ->assertDontSee('Keterampilan yang dibutuhkan proyek ditandai');
    }

    public function test_detail_of_other_umkm_application_is_forbidden(): void
    {
        $mine = $this->umkm();
        $foreign = $this->apply($this->project($this->umkm('Lain')), $this->candidate('Pelamar Rahasia'));

        $this->actingAs($mine->user)->get(route('umkm.seleksi.show', $foreign))->assertForbidden()->assertDontSee('Pelamar Rahasia');
        $this->actingAs($mine->user)->get('/umkm/seleksi/abc')->assertNotFound();
        $this->actingAs($mine->user)->get('/umkm/seleksi/999999')->assertNotFound();
    }

    public function test_back_link_keeps_project_and_status_tab(): void
    {
        $umkm = $this->umkm();
        $project = $this->project($umkm);
        $application = $this->apply($project, $this->candidate());

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', [$application, 'status' => 'pending']))
            ->assertSee(e(route('umkm.seleksi.index', ['proyek' => $project->id, 'status' => 'pending'])), false);
        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', [$application, 'status' => 'ngawur']))
            ->assertSee(route('umkm.seleksi.index', ['proyek' => $project->id]).'"', false);
    }

    // ------------------------------------------------- keamanan portofolio

    public function test_unsafe_portfolio_links_are_not_rendered_as_links(): void
    {
        $umkm = $this->umkm();
        $candidate = $this->candidate();
        $candidate->portfolios()->create(['title' => 'Jahat', 'description' => 'x', 'link' => 'javascript:alert(1)']);
        $application = $this->apply($this->project($umkm), $candidate);

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertSee('Jahat')->assertDontSee('javascript:', false)->assertSee('Tidak ada tautan atau dokumen.');
    }

    public function test_external_links_open_safely(): void
    {
        $umkm = $this->umkm();
        $candidate = $this->candidate();
        $candidate->portfolios()->create(['title' => 'Aman', 'description' => 'x', 'link' => 'https://example.com/karya']);
        $application = $this->apply($this->project($umkm), $candidate);

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))
            ->assertSee('target="_blank" rel="noopener noreferrer nofollow"', false);
    }

    public function test_portfolio_document_access_rules(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('portfolios/karya.pdf', "%PDF-1.1\n%%EOF\n");

        $umkm = $this->umkm();
        $candidate = $this->candidate();
        $doc = $candidate->portfolios()->create(['title' => 'Dok', 'description' => 'x', 'file_path' => 'portfolios/karya.pdf']);
        $missing = $candidate->portfolios()->create(['title' => 'Hilang', 'description' => 'x', 'file_path' => 'portfolios/tidak-ada.pdf']);
        $traversal = $candidate->portfolios()->create(['title' => 'Trav', 'description' => 'x', 'file_path' => '../.env']);
        $application = $this->apply($this->project($umkm), $candidate);

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertSee('karya.pdf')->assertSee('berkas tidak tersedia');

        // Pemilik proyek boleh membuka
        $this->actingAs($umkm->user)->get(route('umkm.seleksi.portofolio', [$application, $doc]))
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        // Berkas hilang / path berbahaya
        $this->actingAs($umkm->user)->get(route('umkm.seleksi.portofolio', [$application, $missing]))->assertNotFound();
        $this->actingAs($umkm->user)->get(route('umkm.seleksi.portofolio', [$application, $traversal]))->assertNotFound();

        // UMKM lain dan kandidat tidak boleh
        $this->actingAs($this->umkm('Lain')->user)->get(route('umkm.seleksi.portofolio', [$application, $doc]))->assertForbidden();
        $this->actingAs($candidate->user)->get(route('umkm.seleksi.portofolio', [$application, $doc]))->assertForbidden();

        // Portofolio kandidat lain tidak boleh dibuka lewat lamaran ini
        $otherDoc = $this->candidate()->portfolios()->create(['title' => 'Milik Lain', 'description' => 'x', 'file_path' => 'portfolios/karya.pdf']);
        $this->actingAs($umkm->user)->get(route('umkm.seleksi.portofolio', [$application, $otherDoc]))->assertNotFound();
    }

    public function test_html_in_user_content_is_escaped(): void
    {
        $umkm = $this->umkm();
        $candidate = $this->candidate('<b>Nama</b>', ['summary' => '<script>alert(1)</script>']);
        $application = $this->apply($this->project($umkm), $candidate, ApplicationStatus::Pending, '<img src=x onerror=alert(1)>');

        $this->actingAs($umkm->user)->get(route('umkm.seleksi.show', $application))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
