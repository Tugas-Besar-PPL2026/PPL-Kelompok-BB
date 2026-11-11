<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidatePortfolioRequest;
use App\Models\CandidateProfile;
use App\Models\Portfolio;
use App\Services\CandidatePortfolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CandidatePortfolioController extends Controller
{
    public function __construct(private CandidatePortfolioService $candidatePortfolios) {}

    public function edit(Request $request)
    {
        abort_unless($request->user()?->isKandidat(), 403);

        $profile = $request->user()->candidateProfile?->load([
            'skills' => fn ($q) => $q->orderBy('skills.id'),
            'experiences' => fn ($q) => $q->orderBy('experiences.id'),
            'portfolios' => fn ($q) => $q->orderBy('portfolios.id'),
        ]);

        $portfolios = $profile?->portfolios ?? collect();
        $editingPortfolio = $request->filled('ubah_portofolio')
            ? $portfolios->firstWhere('id', (int) $request->query('ubah_portofolio'))
            : null;

        return view('kandidat.profil.portofolio', [
            'profile' => $profile,
            'selectedSkills' => $profile?->skills ?? collect(),
            'experiences' => $profile?->experiences ?? collect(),
            'portfolios' => $portfolios,
            'portfolioItems' => $this->candidatePortfolios->formatItems($portfolios),
            'editingPortfolio' => $editingPortfolio,
        ]);
    }

    public function store(CandidatePortfolioRequest $request)
    {
        $profile = $this->resolveProfile($request);
        Gate::authorize('update', $profile);

        $portfolio = $this->candidatePortfolios->store(
            $profile,
            $request->validated(),
            $request->file('file'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Portofolio berhasil ditambahkan.',
                'portfolio' => $portfolio,
            ], 201);
        }

        return redirect()->route('kandidat.profil.portofolio')->with('success', 'Portofolio berhasil ditambahkan.');
    }

    public function update(CandidatePortfolioRequest $request, Portfolio $portfolio)
    {
        Gate::authorize('update', $portfolio);

        $updated = $this->candidatePortfolios->update(
            $portfolio,
            $request->validated(),
            $request->file('file'),
            $request->boolean('remove_file'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Portofolio berhasil diperbarui.',
                'portfolio' => $updated,
            ]);
        }

        return redirect()->route('kandidat.profil.portofolio')->with('success', 'Portofolio berhasil diperbarui.');
    }

    public function destroy(Request $request, Portfolio $portfolio)
    {
        Gate::authorize('delete', $portfolio);

        $this->candidatePortfolios->delete($portfolio);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Portofolio berhasil dihapus.',
            ]);
        }

        return redirect()->route('kandidat.profil.portofolio')->with('success', 'Portofolio berhasil dihapus.');
    }

    public function file(Portfolio $portfolio): Response
    {
        Gate::authorize('view', $portfolio);

        $file = $this->candidatePortfolios->locateFile($portfolio);
        abort_if($file === null, 404, 'Dokumen portofolio tidak ditemukan.');

        $name = basename($file['path']);
        $headers = ['X-Content-Type-Options' => 'nosniff'];
        $disk = Storage::disk($file['disk']);
        $mime = $disk->mimeType($file['path']) ?: '';

        if ($mime === 'application/pdf' || in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return $disk->response($file['path'], $name, $headers);
        }

        return $disk->download($file['path'], $name, $headers);
    }

    private function resolveProfile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_if(! $profile, 403, 'Lengkapi data diri terlebih dahulu.');

        return $profile;
    }
}
