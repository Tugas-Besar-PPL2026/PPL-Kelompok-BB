<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    public function create()
    {
        return view('umkm.proyek.create', $this->formData());
    }

    public function store(ProjectRequest $request)
    {
        // Profil dijamin ada oleh middleware umkm.profile.
        $umkm = $request->user()->umkmProfile;

        $project = $this->projects->saveDraft($umkm, $request->validated());
        if ($request->boolean('lanjut_tinjau')) {
            return redirect()->route('umkm.proyek.review', $project)->with('success', 'Draf proyek disimpan. Periksa kembali sebelum mengajukan.');
        }

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
        if ($request->boolean('lanjut_tinjau')) {
            return redirect()->route('umkm.proyek.review', $project)->with('success', 'Draf proyek diperbarui. Periksa kembali sebelum mengajukan.');
        }

        return back()->with('success', 'Draf proyek diperbarui.');
    }

        public function review(Project $project)
    {
        Gate::authorize('update', $project);

        return view('umkm.proyek.review', [
            'project' => $project->load('category', 'skills', 'paymentTerms', 'umkmProfile'),
            'checks' => $this->projects->submissionChecks($project),
            'canSubmit' => in_array($project->status, ProjectService::EDITABLE, true),
        ]);
    }

    public function submit(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $request->validate(
            ['pernyataan' => ['accepted']],
            ['pernyataan.accepted' => 'Centang pernyataan terlebih dahulu.']
        );

        $this->projects->submitForModeration($project);

        return redirect()->route('umkm.proyek.review', $project)->with('success', 'Proyek diajukan dan menunggu moderasi admin.');
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
