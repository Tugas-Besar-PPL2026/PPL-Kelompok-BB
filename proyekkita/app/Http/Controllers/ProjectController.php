<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Category;
use App\Models\Skill;
use App\Services\ProjectService;

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

        $this->projects->saveDraft($umkm, $request->validated());

        return redirect()->route('umkm.proyek.create')->with('success', 'Draf proyek disimpan.');
    }

    private function formData(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->get(),
            'skills' => Skill::active()->orderBy('name')->get(),
        ];
    }
}
