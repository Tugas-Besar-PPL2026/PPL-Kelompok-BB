<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateSkillRequest;
use App\Models\CandidateProfile;
use App\Models\Skill;
use App\Services\CandidateSkillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateSkillController extends Controller
{
    public function __construct(private CandidateSkillService $candidateSkills) {}

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

    public function store(CandidateSkillRequest $request)
    {
        $profile = $this->resolveProfile($request);
        Gate::authorize('update', $profile);

        if ($request->has('skills') && ! $request->has('skill_id')) {
            $this->candidateSkills->syncSkills($profile, $request->validated('skills', []));

            return $this->respond($request, $profile, 'Keterampilan berhasil diperbarui.');
        }

        $added = $this->candidateSkills->addSkill($profile, (int) $request->validated('skill_id'));
        $message = $added ? 'Keterampilan berhasil ditambahkan.' : 'Keterampilan sudah dipilih sebelumnya.';

        return $this->respond($request, $profile, $message);
    }

    public function update(CandidateSkillRequest $request)
    {
        $profile = $this->resolveProfile($request);
        Gate::authorize('update', $profile);

        $this->candidateSkills->syncSkills($profile, $request->validated('skills', []));

        return $this->respond($request, $profile, 'Keterampilan berhasil diperbarui.');
    }

    public function destroy(Request $request, Skill $skill)
    {
        $profile = $this->resolveProfile($request);
        Gate::authorize('update', $profile);

        $this->candidateSkills->removeSkill($profile, $skill);

        return $this->respond($request, $profile, 'Keterampilan berhasil dihapus.');
    }

    private function resolveProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_if(! $profile, 403, 'Lengkapi data diri terlebih dahulu.');

        return $profile;
    }

    private function respond(Request $request, CandidateProfile $profile, string $message)
    {
        if ($request->wantsJson()) {
            $selected = $profile->skills()->orderBy('skills.id')->get(['skills.id', 'skills.name']);

            return response()->json([
                'message' => $message,
                'selected_skills' => $selected,
                'count' => $selected->count(),
            ]);
        }

        $query = trim((string) $request->input('q', ''));
        $params = $query !== '' ? ['q' => $query] : [];

        return redirect()->route('kandidat.profil.edit', $params)->with('success', $message);
    }
}
