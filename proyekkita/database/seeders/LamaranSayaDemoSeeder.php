<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\Project;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * [PBI-17] Data contoh KHUSUS untuk mencoba halaman Lamaran Saya secara lokal.
 *
 * Seeder yang sudah ada (SelectionDemoSeeder) hanya memberi kandidat Fajar Pratama satu lamaran berstatus Diterima.
 * Seeder ini memakai data tersebut apa adanya, lalu MENAMBAH empat lamaran Fajar pada proyek lain supaya semua status
 * pada mockup (Terpilih Sementara, Menunggu Keputusan, Ditolak, Dibatalkan) bisa dilihat. Teks, status, dan tanggal
 * mengikuti mockup Lamaran Saya; jam yang tidak tertulis pada mockup dipilih sendiri untuk keperluan contoh.
 *
 * Aman dijalankan berulang (idempotent), tidak mengubah seeder lain, dan ditolak di production.
 *
 *     php artisan db:seed --class=LamaranSayaDemoSeeder
 */
class LamaranSayaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LamaranSayaDemoSeeder tidak boleh dijalankan di production.');
        }

        // Data dasar: Fajar Pratama, Kopi Nusantara, dan lamaran Diterima (milik seeder anggota lain).
        $this->call(SelectionDemoSeeder::class);

        $fajar = User::where('email', 'kandidat@proyekkita.test')->firstOrFail()->candidateProfile;
        $kopiNusantara = Project::where('title', 'Redesain Katalog Digital & Label Kemasan')->firstOrFail()->umkmProfile;

        foreach ($this->applications() as $data) {
            $umkm = $data['umkm'] === null
                ? $kopiNusantara
                : $this->umkm($data['umkm']);

            $project = $this->project($umkm, $data['project'], $data['umkm'] === null ? 'Kopi Nusantara' : $data['umkm'][0]);
            $this->application($fajar, $project, $data);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applications(): array
    {
        return [
            [
                // Terpilih Sementara, riwayat 2 entri (Lamaran dikirim -> Terpilih sementara)
                'umkm' => ['Kedai Teh Sinar', 'umkm.tehsinar@proyekkita.test', 'Kuliner & Minuman', 'Kab. Bandung'],
                'project' => 'Desain UI Menu Digital Berbasis QR',
                'status' => ApplicationStatus::Shortlisted,
                'applied' => '2026-11-03 20:14:00',
                'message' => 'Halo Kedai Teh Sinar! Saya tertarik merancang antarmuka menu digital berbasis QR karena sudah terbiasa mendesain tampilan untuk UMKM kuliner.',
                'note' => 'Anda masuk daftar pendek. Keputusan akhir paling lambat 12 Nov 2026.',
                'histories' => [[ApplicationStatus::Shortlisted, '2026-11-06 11:30:00', 'Anda masuk daftar pendek. Keputusan akhir paling lambat 12 Nov 2026.']],
            ],
            [
                // Menunggu Keputusan, hanya riwayat awal dari PBI-16
                'umkm' => null, // Kopi Nusantara
                'project' => 'Desain Menu Musiman Kedai',
                'status' => ApplicationStatus::Pending,
                'applied' => '2026-11-05 09:00:00',
                'message' => 'Saya ingin membantu menyusun desain menu musiman Kopi Nusantara dengan gaya visual yang konsisten dengan label kemasan.',
                'note' => null,
                'histories' => [],
            ],
            [
                // Ditolak dengan catatan seleksi
                'umkm' => ['Keripik Bu Darmi', 'umkm.keripikdarmi@proyekkita.test', 'Kuliner & Minuman', 'Kota Bandung'],
                'project' => 'Konten Media Sosial & Foto Produk',
                'status' => ApplicationStatus::Rejected,
                'applied' => '2026-11-02 08:30:00',
                'message' => 'Saya bisa membantu membuat konten media sosial dan foto produk keripik dengan gaya yang menarik.',
                'note' => 'Terima kasih atas lamarannya. Kami memilih kandidat yang memiliki pengalaman fotografi produk.',
                'histories' => [[ApplicationStatus::Rejected, '2026-11-04 09:00:00', 'Terima kasih atas lamarannya. Kami memilih kandidat yang memiliki pengalaman fotografi produk.']],
            ],
            [
                // Dibatalkan oleh kandidat. Entri pembatalan ditulis di sini hanya sebagai data contoh;
                // pada aplikasi sebenarnya entri itu dibuat oleh PBI-18.
                'umkm' => ['Toko Berkah Mandiri', 'umkm.berkahmandiri@proyekkita.test', 'Perdagangan', 'Kota Bandung'],
                'project' => 'Sistem Kasir Web Sederhana',
                'status' => ApplicationStatus::Cancelled,
                'applied' => '2026-11-02 14:00:00',
                'message' => 'Saya tertarik membangun sistem kasir web sederhana untuk Toko Berkah Mandiri.',
                'note' => null,
                'histories' => [[ApplicationStatus::Cancelled, '2026-11-03 10:00:00', null]],
            ],
        ];
    }

    /** @param  array{0: string, 1: string, 2: string, 3: string}  $data nama usaha, email, bidang, kota */
    private function umkm(array $data): UmkmProfile
    {
        [$name, $email, $field, $city] = $data;

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name, 'password' => 'password', 'role' => UserRole::Umkm,
        ]);

        return $user->umkmProfile()->updateOrCreate([], [
            'business_name' => $name,
            'description' => 'Usaha '.$name.' (data contoh Lamaran Saya).',
            'business_field' => $field,
            'contact_phone' => '0812-0000-0000',
            'city' => $city,
            'address' => 'Jl. Contoh No. 1, '.$city,
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    private function project(UmkmProfile $umkm, string $title, string $owner): Project
    {
        $project = $umkm->projects()->updateOrCreate(['title' => $title], [
            'category_id' => Category::where('name', 'Desain Grafis & Branding')->value('id') ?? Category::value('id'),
            'description' => 'Proyek contoh "'.$title.'" milik '.$owner.'.',
            'expected_output' => "Hasil pekerjaan sesuai kebutuhan proyek\nFile sumber yang dapat diedit",
            'application_deadline' => now()->addDays(16)->toDateString(),
            'target_completion_date' => now()->addDays(41)->toDateString(),
            'quota' => 1,
            'total_compensation' => 1000000,
            'status' => ProjectStatus::Published,
            'published_at' => '2026-11-01 09:00:00',
        ]);

        $project->paymentTerms()->updateOrCreate(['term_number' => 1], ['amount' => 500000, 'condition' => 'Uang muka setelah kesepakatan berstatus aktif']);
        $project->paymentTerms()->updateOrCreate(['term_number' => 2], ['amount' => 500000, 'condition' => 'Pelunasan setelah hasil akhir disetujui UMKM']);

        return $project;
    }

    /** @param  array<string, mixed>  $data */
    private function application(CandidateProfile $profile, Project $project, array $data): void
    {
        $application = Application::firstOrNew(['project_id' => $project->id, 'candidate_profile_id' => $profile->id]);
        $application->forceFill([
            'message' => $data['message'],
            'status' => $data['status'],
            'selection_note' => $data['note'],
            'created_at' => $data['applied'],
        ])->save();

        // Riwayat: entri awal Menunggu Keputusan (seperti yang dicatat PBI-16), lalu perubahan berikutnya.
        ApplicationStatusHistory::where('application_id', $application->id)->delete();
        $initialStatus = $data['status'] === ApplicationStatus::Cancelled || $data['histories'] !== []
            ? ApplicationStatus::Pending
            : $data['status'];
        ApplicationStatusHistory::create([
            'application_id' => $application->id, 'status' => $initialStatus, 'note' => null, 'changed_at' => $data['applied'],
        ]);

        foreach ($data['histories'] as [$status, $changedAt, $note]) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id, 'status' => $status, 'note' => $note, 'changed_at' => $changedAt,
            ]);
        }
    }
}
