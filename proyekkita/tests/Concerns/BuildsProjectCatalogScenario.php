<?php

namespace Tests\Concerns;

use App\Enums\ApplicationStatus;
use App\Enums\ProjectStatus;
use App\Enums\ModerationDecision;
use App\Models\Application;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectModeration;
use App\Models\Skill;
use App\Models\User;
use App\Models\UmkmProfile;

trait BuildsProjectCatalogScenario
{
    use BuildsApplicationScenario;

    protected function makeCategory(string $name): Category
    {
        return Category::firstOrCreate([
            'name' => $name,
        ]);
    }

    protected function makeSkill(string $name): Skill
    {
        return Skill::firstOrCreate([
            'name' => $name,
        ]);
    }

    protected function makeCatalogProject(
        string $title,
        array $overrides = [],
        ?Category $category = null,
        array $skills = [],
        ?UmkmProfile $umkmProfile = null
    ): Project {
        $umkmProfile ??= $this->makeUmkm()->umkmProfile;

        $project = Project::create(array_merge([
            'umkm_profile_id' => $umkmProfile->id,
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

    protected function makeModeration(
        Project $project,
        ModerationDecision $decision,
        ?string $note = null
    ): ProjectModeration {
        return ProjectModeration::create([
            'project_id' => $project->id,
            'admin_id' => User::factory()->admin()->create()->id,
            'decision' => $decision,
            'note' => $note,
        ]);
    }
}
