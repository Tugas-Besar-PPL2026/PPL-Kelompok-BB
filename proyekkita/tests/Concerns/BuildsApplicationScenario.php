<?php

namespace Tests\Concerns;

use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Pembuat data uji untuk PBI-14 dan PBI-16. Repository belum punya factory selain User,
 * jadi data dibuat langsung lewat model dan hanya dipakai di test.
 */
trait BuildsApplicationScenario
{
    /** Kandidat dengan profil lengkap. $profile menimpa isi profil, jumlah lainnya mengatur relasi. */
    protected function makeKandidat(array $profile = [], int $skills = 1, int $experiences = 0, int $portfolios = 0): User
    {
        $user = User::factory()->kandidat()->create();

        $candidate = $user->candidateProfile()->create(array_merge([
            'full_name' => $user->name,
            'headline' => 'Desainer grafis',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang fokus pada desain kemasan.',
        ], $profile));

        $candidate->skills()->sync(
            collect(range(1, max($skills, 0)))->take($skills)
                ->map(fn () => Skill::create(['name' => 'Skill ' . Str::random(8)])->id)->all()
        );

        for ($i = 1; $i <= $experiences; $i++) {
            $candidate->experiences()->create(['work_type' => "Freelance {$i}", 'duration' => '6 bulan', 'description' => 'Pekerjaan lepas.']);
        }
        for ($i = 1; $i <= $portfolios; $i++) {
            $candidate->portfolios()->create(['title' => "Karya {$i}", 'description' => 'Karya contoh.']);
        }

        return $user;
    }

    protected function makeUmkm(array $profile = []): User
    {
        $user = User::factory()->umkm()->create();
        $user->umkmProfile()->create(array_merge([
            'business_name' => 'Kopi Nusantara',
            'description' => 'Roastery kopi lokal.',
            'business_field' => 'Kuliner & Minuman',
            'contact_phone' => '081234567890',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1, Bandung',
            'verification_status' => VerificationStatus::Verified,
        ], $profile));

        return $user;
    }

    /** Proyek dipublikasikan, tenggat 10 hari lagi, 2 termin. $overrides menimpa kolom proyek. */
    protected function makeProject(array $overrides = [], ?UmkmProfile $umkm = null): Project
    {
        $umkm ??= $this->makeUmkm()->umkmProfile;
        $category = Category::firstOrCreate(['name' => 'Desain Grafis & Branding']);

        $project = Project::create(array_merge([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $category->id,
            'title' => 'Redesain Katalog Digital & Label Kemasan',
            'description' => 'Memperbarui label kemasan tiga varian biji kopi.',
            'expected_output' => "• Desain label kemasan 3 SKU\n• Katalog produk digital 8 halaman\nFile sumber Figma",
            'application_deadline' => now()->addDays(10)->toDateString(),
            'target_completion_date' => now()->addDays(40)->toDateString(),
            'quota' => 2,
            'total_compensation' => 2500000,
            'status' => ProjectStatus::Published,
            'published_at' => now()->subDays(3),
        ], $overrides));

        $project->skills()->sync([Skill::create(['name' => 'Figma ' . Str::random(6)])->id]);
        $project->paymentTerms()->create(['term_number' => 1, 'amount' => 1250000, 'condition' => 'Uang muka setelah kesepakatan aktif']);
        $project->paymentTerms()->create(['term_number' => 2, 'amount' => 1250000, 'condition' => 'Pelunasan setelah hasil akhir disetujui']);

        return $project;
    }

    /** Pesan lamaran yang sah (minimal 50 karakter). */
    protected function validMessage(): string
    {
        return 'Halo, saya berpengalaman mendesain label kemasan UMKM dan tertarik membantu proyek ini.';
    }
}
