<?php

namespace Tests\Concerns;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Models\Agreement;
use App\Models\Application;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;

trait BuildsProjectCatalogScenario
{
    use BuildsApplicationScenario;

    /**
     * Membuat kategori proyek untuk kebutuhan testing.
     */
    protected function makeCategory(string $name): Category
    {
        return Category::firstOrCreate([
            'name' => $name,
        ]);
    }

    /**
     * Membuat keterampilan untuk kebutuhan testing.
     */
    protected function makeSkill(string $name): Skill
    {
        return Skill::firstOrCreate([
            'name' => $name,
        ]);
    }

    /**
     * Membuat proyek untuk pengujian katalog proyek.
     */
    protected function makeCatalogProject(
        string $title,
        array $overrides = [],
        ?Category $category = null,
        array $skills = []
    ): Project {
        $umkm = $this->makeUmkm()->umkmProfile;

        $project = Project::create(array_merge([
            'umkm_profile_id' => $umkm->id,
            'category_id' => $category?->id
                ?? $this->makeCategory('Desain Grafis & Branding')->id,
            'title' => $title,
            'description' => 'Deskripsi proyek untuk pengujian katalog.',
            'expected_output' => 'Hasil proyek sesuai kebutuhan UMKM.',
            'application_deadline' => now()->addDays(20)->toDateString(),
            'target_completion_date' => now()->addDays(40)->toDateString(),
            'quota' => 2,
            'total_compensation' => 1500000,
            'status' => ProjectStatus::Published,
            'published_at' => now()->subDays(3),
        ], $overrides));

        $project->skills()->sync(
            collect($skills)->map(fn ($skill) => $skill->id)->all()
        );

        return $project;
    }

    /**
     * Membuat lamaran kandidat pada proyek tertentu.
     */
    protected function makeApplicationFor(
        User $candidate,
        ApplicationStatus $status,
        array $overrides = []
    ): Application {
        $project = $overrides['project'] ?? null;

        unset($overrides['project']);

        return Application::create(array_merge([
            'project_id' => $project?->id,
            'candidate_profile_id' => $candidate->candidateProfile->id,
            'message' => 'Saya tertarik mengikuti proyek ini.',
            'status' => $status,
        ], $overrides));
    }

    /**
     * Membuat evaluasi kandidat terhadap UMKM.
     */
    protected function makeEvaluationFor(
        User $umkm,
        int $rating
    ): Evaluation {
        $candidate = $this->makeKandidat();

        $project = $this->makeProject([], $umkm->umkmProfile);

        $application = $this->makeApplicationFor(
            $candidate,
            ApplicationStatus::Accepted,
            ['project' => $project]
        );

        $agreement = Agreement::create([
            'application_id' => $application->id,
            'scope' => 'Pengerjaan proyek sesuai kebutuhan UMKM.',
            'deliverables' => 'Hasil akhir proyek.',
            'deadline' => now()->addDays(30)->toDateString(),
            'total_compensation' => $project->total_compensation,
            'revision_limit' => 2,
            'status' => AgreementStatus::Completed,
        ]);

        return Evaluation::create([
            'agreement_id' => $agreement->id,
            'reviewer_id' => $candidate->id,
            'reviewee_id' => $umkm->id,
            'rating' => $rating,
            'comment' => 'Evaluasi untuk kebutuhan pengujian.',
        ]);
    }
}
