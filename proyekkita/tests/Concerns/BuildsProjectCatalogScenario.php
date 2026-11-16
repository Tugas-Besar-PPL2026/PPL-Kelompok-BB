<?php

namespace Tests\Concerns;

use App\Enums\ProjectStatus;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;

trait BuildsProjectCatalogScenario
{
    use BuildsApplicationScenario;

    protected function makeCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
        ]);
    }

    protected function makeSkill(string $name): Skill
    {
        return Skill::create([
            'name' => $name,
        ]);
    }

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
                ?? Category::firstOrCreate([
                    'name' => 'Desain Grafis & Branding',
                ])->id,
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
}
