<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateExperienceRequest;
use App\Models\CandidateProfile;
use App\Models\Experience;
use App\Models\Skill;
use App\Services\CandidateExperienceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateExperienceController extends Controller
{
    public function __construct(private CandidateExperienceService $candidateExperiences) {}

    public function edit(Request $request)
    {
        abort_unless($request->user()?->isKandidat(), 403);

        $profile = $request->user()->candidateProfile?->load([
            'skills' => fn ($q) => $q->orderBy('skills.id'),
            'experiences' => fn ($q) => $q->orderBy('experiences.id'),
            'portfolios' => fn ($q) => $q->orderBy('portfolios.id'),
        ]);

        $experiences = $profile?->experiences ?? collect();
        $editingExp = $request->filled('ubah_pengalaman')
            ? $experiences->firstWhere('id', (int) $request->query('ubah_pengalaman'))
            : null;

        return view('kandidat.profil.edit', [
            'profile' => $profile,
            'skills' => Skill::active()->orderBy('id')->get(),
            'selectedSkills' => $profile?->skills ?? collect(),
            'experiences' => $experiences,
            'editingExp' => $editingExp,
            'portfolios' => $profile?->portfolios ?? collect(),
            'searchQuery' => (string) $request->query('q', ''),
        ]);
    }

    public function store(CandidateExperienceRequest $request)
    {
        $profile = $this->resolveProfile($request);
        Gate::authorize('update', $profile);

        $experience = $this->candidateExperiences->store($profile, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pengalaman kerja berhasil ditambahkan.',
                'experience' => $experience,
            ], 201);
        }

        return redirect()->route('kandidat.profil.edit')->with('success', 'Pengalaman kerja berhasil ditambahkan.');
    }

    public function update(CandidateExperienceRequest $request, Experience $experience)
    {
        Gate::authorize('update', $experience);

        $updated = $this->candidateExperiences->update($experience, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pengalaman kerja berhasil diperbarui.',
                'experience' => $updated,
            ]);
        }

        return redirect()->route('kandidat.profil.edit')->with('success', 'Pengalaman kerja berhasil diperbarui.');
    }

    public function destroy(Request $request, Experience $experience)
    {
        Gate::authorize('delete', $experience);

        $this->candidateExperiences->delete($experience);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Pengalaman kerja berhasil dihapus.',
            ]);
        }

        return redirect()->route('kandidat.profil.edit')->with('success', 'Pengalaman kerja berhasil dihapus.');
    }

    private function resolveProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_if(! $profile, 403, 'Lengkapi data diri terlebih dahulu.');

        return $profile;
    }
}
