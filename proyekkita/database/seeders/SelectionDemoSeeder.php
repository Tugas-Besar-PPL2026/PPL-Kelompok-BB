<?php

namespace Database\Seeders;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Project;
use App\Models\Skill;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SelectionDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('SelectionDemoSeeder tidak boleh dijalankan di production.');
        }

        $this->call([MasterDataSeeder::class, DemoSeeder::class]);

        $project = Project::where('title', 'Redesain Katalog Digital & Label Kemasan')->firstOrFail();
        $project->update(['published_at' => '2026-11-03 09:00:00', 'quota' => 2]);

        $pastClient = $this->pastClient();
        Storage::disk('local')->put('portfolios/feed-kedai-roti.pdf', $this->tinyPdf());

        foreach ($this->candidates() as $data) {
            $profile = $this->candidate($data);

            foreach ($data['past_ratings'] as $i => $rating) {
                $this->completedJob($profile, $pastClient, $data['name'].' - proyek selesai '.($i + 1), $rating);
            }

            $this->application($project, $profile, $data);
        }
    }

    /** @return list<array<string, mixed>> */
    private function candidates(): array
    {
        return [
            [
                'email' => 'kandidat@proyekkita.test', 'name' => 'Fajar Pratama',
                'headline' => 'Desainer grafis & kemasan produk UMKM', 'university' => 'Telkom University',
                'major' => 'S1 Desain Komunikasi Visual', 'city' => 'Kota Bandung',
                'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan dan identitas visual produk UMKM.',
                'skills' => null, // memakai keterampilan dari DemoSeeder
                'experiences' => [
                    ['Desain kemasan produk UMKM (freelance)', '8 bulan', 'Merancang label dan kemasan untuk 4 UMKM makanan ringan di Bandung.'],
                    ['Desain materi promosi kedai kopi kampus', '6 bulan', 'Membuat poster, menu, dan konten media sosial untuk kedai kopi kampus.'],
                ],
                'portfolios' => [],
                'past_ratings' => [5, 5, 4],
                'status' => ApplicationStatus::Accepted, 'applied' => '2026-11-04 19:40:00',
                'message' => 'Halo Kopi Nusantara! Saya sudah beberapa kali merancang label kemasan untuk UMKM makanan dan siap membantu redesain label serta katalog digitalnya.',
                'histories' => [[ApplicationStatus::Accepted, '2026-11-05 10:15:00', 'Portofolio kemasan paling sesuai dengan kebutuhan label.']],
            ],
            [
                'email' => 'nadia@proyekkita.test', 'name' => 'Nadia Putri Rahma',
                'headline' => 'Ilustrator & desainer grafis untuk produk makanan', 'university' => 'Universitas Pendidikan Indonesia',
                'major' => 'S1 Desain Komunikasi Visual', 'city' => 'Kota Bandung',
                'summary' => 'Mahasiswa DKV semester 7 yang senang membuat ilustrasi untuk kemasan dan media sosial produk makanan. Terbiasa menggunakan Figma dan Procreate.',
                'skills' => ['Figma', 'Desain Grafis', 'Ilustrasi'],
                'experiences' => [
                    ['Ilustrasi kemasan makanan ringan (freelance)', '6 bulan', 'Membuat ilustrasi untuk 5 kemasan keripik dan sambal UMKM.'],
                    ['Desain konten media sosial UKM kampus', '4 bulan', 'Mengelola desain feed dan story untuk 2 UKM kampus.'],
                ],
                'portfolios' => [
                    ['Label Kemasan Sambal Rumahan', 'Ilustrasi dan tata letak label untuk tiga varian sambal.', 'behance.net/nadiaputri/sambal', null],
                    ['Ilustrasi Feed Kedai Roti', 'Seri ilustrasi feed Instagram untuk kedai roti.', null, 'portfolios/feed-kedai-roti.pdf'],
                ],
                'past_ratings' => [5, 4],
                'status' => ApplicationStatus::Shortlisted, 'applied' => '2026-11-04 20:11:00',
                'message' => 'Halo Kopi Nusantara! Saya tertarik dengan proyek redesain label karena memiliki pengalaman membuat ilustrasi kemasan makanan ringan. Saya bisa menawarkan gaya ilustrasi khas daerah asal biji kopi untuk tiap varian.',
                'histories' => [[ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Portofolio ilustrasi kuat, dibandingkan dengan pelamar lain.']],
            ],
            [
                'email' => 'raka@proyekkita.test', 'name' => 'Raka Aditya',
                'headline' => 'Desainer visual yang suka merancang kemasan', 'university' => 'Institut Teknologi Nasional',
                'major' => 'S1 Desain Produk', 'city' => 'Kota Cimahi',
                'summary' => 'Mahasiswa desain produk yang tertarik pada kemasan ramah lingkungan.',
                'skills' => ['Desain Grafis', 'Desain Kemasan', 'Canva'],
                'experiences' => [['Desain kemasan produk kerajinan', '6 bulan', 'Membuat desain kemasan untuk produk kerajinan lokal.']],
                'portfolios' => [],
                'past_ratings' => [],
                'status' => ApplicationStatus::Pending, 'applied' => '2026-11-05 08:20:00',
                'message' => 'Saya ingin membantu merapikan label dan katalog Kopi Nusantara.',
                'histories' => [],
            ],
            [
                'email' => 'salsabila@proyekkita.test', 'name' => 'Salsabila Nur',
                'headline' => 'Content writer dan desainer konten media sosial', 'university' => 'Universitas Padjadjaran',
                'major' => 'S1 Ilmu Komunikasi', 'city' => 'Kab. Sumedang',
                'summary' => 'Mahasiswa komunikasi yang terbiasa menulis copy produk dan mengelola media sosial UMKM.',
                'skills' => ['Canva', 'Copywriting'],
                'experiences' => [['Admin media sosial UMKM kuliner', '4 bulan', 'Menulis caption dan mendesain konten harian.']],
                'portfolios' => [],
                'past_ratings' => [],
                'status' => ApplicationStatus::Pending, 'applied' => '2026-11-05 13:05:00',
                'message' => "Saya bisa membantu menyusun deskripsi produk di katalog digital.\nTerima kasih atas kesempatannya.",
                'histories' => [],
            ],
            [
                'email' => 'dimas@proyekkita.test', 'name' => 'Dimas Prakoso',
                'headline' => 'UI/UX designer pemula', 'university' => 'Universitas Komputer Indonesia',
                'major' => 'S1 Teknik Informatika', 'city' => 'Kota Bandung',
                'summary' => 'Mahasiswa informatika yang berfokus pada desain antarmuka aplikasi.',
                'skills' => ['Figma', 'UI/UX Design'],
                'experiences' => [['Desain antarmuka aplikasi kampus', '1 tahun', 'Merancang prototipe aplikasi layanan kampus.']],
                'portfolios' => [],
                'past_ratings' => [4],
                'status' => ApplicationStatus::Rejected, 'applied' => '2026-11-04 21:30:00',
                'message' => 'Saya ingin mencoba proyek desain di luar bidang antarmuka.',
                'histories' => [[ApplicationStatus::Rejected, '2026-11-05 15:00:00', 'Pengalaman kemasan belum sesuai kebutuhan proyek ini.']],
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function candidate(array $data): CandidateProfile
    {
        $user = User::updateOrCreate(['email' => $data['email']], [
            'name' => $data['name'], 'password' => 'password', 'role' => UserRole::Kandidat,
        ]);

        $profile = $user->candidateProfile;
        if (! $profile) {
            $profile = $user->candidateProfile()->create([
                'full_name' => $data['name'], 'headline' => $data['headline'], 'university' => $data['university'],
                'major' => $data['major'], 'city' => $data['city'], 'summary' => $data['summary'],
            ]);
        }

        if ($data['skills'] !== null) {
            $profile->skills()->sync(Skill::whereIn('name', $data['skills'])->pluck('id'));
        }

        foreach ($data['experiences'] as [$workType, $duration, $description]) {
            $profile->experiences()->firstOrCreate(['work_type' => $workType], ['duration' => $duration, 'description' => $description]);
        }

        foreach ($data['portfolios'] as [$title, $description, $link, $file]) {
            $profile->portfolios()->firstOrCreate(['title' => $title], ['description' => $description, 'link' => $link, 'file_path' => $file]);
        }

        return $profile;
    }

    /** @param  array<string, mixed>  $data */
    private function application(Project $project, CandidateProfile $profile, array $data): void
    {
        $application = Application::firstOrNew(['project_id' => $project->id, 'candidate_profile_id' => $profile->id]);
        $application->forceFill([
            'message' => $data['message'],
            'status' => $data['status'],
            'selection_note' => $data['histories'][0][2] ?? null,
            'created_at' => $data['applied'],
        ])->save();

        ApplicationStatusHistory::where('application_id', $application->id)->delete();
        ApplicationStatusHistory::create([
            'application_id' => $application->id, 'status' => ApplicationStatus::Pending, 'note' => null, 'changed_at' => $data['applied'],
        ]);
        foreach ($data['histories'] as [$status, $at, $note]) {
            ApplicationStatusHistory::create(['application_id' => $application->id, 'status' => $status, 'note' => $note, 'changed_at' => $at]);
        }
    }

    /** UMKM lain yang memberi evaluasi pada proyek-proyek lampau kandidat. */
    private function pastClient(): UmkmProfile
    {
        $user = User::updateOrCreate(['email' => 'umkm.riwayat@proyekkita.test'], [
            'name' => 'Sekar Wulandari', 'password' => 'password', 'role' => UserRole::Umkm,
        ]);

        return $user->umkmProfile()->updateOrCreate([], [
            'business_name' => 'Batik Sekar Wangi', 'description' => 'Butik batik tulis dan cap di Bandung.',
            'business_field' => 'Fesyen', 'contact_phone' => '0813-1122-3344', 'city' => 'Bandung',
            'address' => 'Jl. Braga No. 10, Kota Bandung', 'verification_status' => VerificationStatus::Verified, 'verified_at' => now(),
        ]);
    }

    /** Satu proyek selesai lengkap dengan kesepakatan Selesai dan evaluasi UMKM kepada kandidat. */
    private function completedJob(CandidateProfile $profile, UmkmProfile $client, string $title, int $rating): void
    {
        $project = $client->projects()->updateOrCreate(['title' => $title], [
            'category_id' => Category::value('id'), 'description' => 'Proyek selesai untuk data riwayat.',
            'expected_output' => 'Hasil desain', 'application_deadline' => '2026-08-01', 'target_completion_date' => '2026-09-01',
            'quota' => 1, 'total_compensation' => 1000000, 'status' => ProjectStatus::Closed, 'published_at' => '2026-07-01 08:00:00',
        ]);

        $application = Application::firstOrCreate(
            ['project_id' => $project->id, 'candidate_profile_id' => $profile->id],
            ['message' => 'Lamaran proyek lampau.', 'status' => ApplicationStatus::Accepted],
        );

        $agreement = Agreement::firstOrCreate(['application_id' => $application->id], [
            'scope' => 'Desain sesuai brief', 'deliverables' => 'File desain final', 'deadline' => '2026-09-01',
            'total_compensation' => 1000000, 'revision_limit' => 2, 'status' => AgreementStatus::Completed,
            'sent_at' => '2026-08-05 09:00:00', 'approved_at' => '2026-08-06 09:00:00',
        ]);

        Evaluation::firstOrCreate(
            ['agreement_id' => $agreement->id, 'reviewer_id' => $client->user_id],
            ['reviewee_id' => $profile->user_id, 'rating' => $rating, 'comment' => 'Pekerjaan rapi dan tepat waktu.'],
        );
    }

    /** PDF paling sederhana yang valid, hanya untuk menguji pembukaan dokumen portofolio. */
    private function tinyPdf(): string
    {
        return "%PDF-1.1\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }
}
