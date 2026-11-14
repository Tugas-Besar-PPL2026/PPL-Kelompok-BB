<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    public function create()
    {
        return view('umkm.proyek.create', $this->formData());
    }

    public function store(ProjectRequest $request)
    {
        $umkm = $request->user()->umkmProfile;
        abort_if(! $umkm, 403, 'Lengkapi profil usaha terlebih dahulu.');

        $project = $this->projects->saveDraft($umkm, $request->validated());

        return redirect()->route('umkm.proyek.edit', $project)->with('success', 'Draf proyek disimpan.');
    }

    public function edit(Project $project)
    {
        $this->authorizeDraft($project);

        return view('umkm.proyek.edit', $this->formData() + ['project' => $project->load('skills', 'paymentTerms')]);
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $this->authorizeDraft($project);
        $this->projects->saveDraft($project->umkmProfile, $request->validated(), $project);

        return back()->with('success', 'Draf proyek diperbarui.');
    }

    /** Hanya pemilik proyek, dan hanya selama proyek masih berstatus draf atau perlu perbaikan. */
    private function authorizeDraft(Project $project): void
    {
        Gate::authorize('update', $project);
        abort_unless(in_array($project->status, ProjectService::EDITABLE, true), 403, 'Proyek ini tidak bisa diubah sebagai draf.');
    }

    private function formData(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->get(),
            'skills' => Skill::active()->orderBy('name')->get(),
        ];
    }
}
