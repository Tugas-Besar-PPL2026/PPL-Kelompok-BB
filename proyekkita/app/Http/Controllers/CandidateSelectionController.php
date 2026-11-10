<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Portfolio;
use App\Models\Project;
use App\Services\CandidateSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CandidateSelectionController extends Controller
{
    public function __construct(private CandidateSelectionService $selection) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Application::class);

        $umkm = $request->user()->umkmProfile;
        abort_if(! $umkm, 403, 'Lengkapi profil usaha terlebih dahulu.');

        $projects = $this->selection->projectsFor($umkm);

        if ($projects->isEmpty()) {
            return view('umkm.seleksi.index', ['projects' => $projects, 'project' => null]);
        }

        $project = $this->requestedProject($request, $projects);
        $status = $this->selection->statusFromQuery($request->query('status'));

        $counts = $this->selection->counts($project);

        return view('umkm.seleksi.index', [
            'projects' => $projects,
            'project' => $project,
            'status' => $status,
            'counts' => $counts,
            'tabs' => $this->selection->tabs($counts),
            'quota' => $this->selection->quota($project, $counts['accepted']),
            'since' => $this->selection->listedSince($project, $counts['all']),
            'applicants' => $this->selection->applicants($project, $status),
        ]);
    }

    public function show(Request $request, Application $application)
    {
        Gate::authorize('view', $application);

        $this->selection->loadDetail($application);
        $profile = $application->candidateProfile;

        return view('umkm.seleksi.show', [
            'application' => $application,
            'project' => $application->project,
            'profile' => $profile,
            'stats' => $this->selection->candidateStats(collect([$profile]))[$profile->id],
            'quota' => $this->selection->quota($application->project),
            'skills' => $this->selection->skillBadges($application),
            'portfolios' => $this->selection->portfolioItems($application),
            'histories' => $application->statusHistories,
            'backStatus' => $this->selection->statusFromQuery($request->query('status')),
        ]);
    }

    public function portfolio(Application $application, Portfolio $portfolio): Response
    {
        Gate::authorize('view', $application);
        abort_unless($portfolio->candidate_profile_id === $application->candidate_profile_id, 404);

        $file = $this->selection->locateFile($portfolio);
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

    private function requestedProject(Request $request, Collection $projects): Project
    {
        $id = $request->query('proyek');
        if ($id === null || $id === '') {
            return $projects->first();
        }

        abort_unless(is_string($id) && ctype_digit($id), 404);

        $project = Project::findOrFail((int) $id);
        Gate::authorize('viewApplicants', $project);

        return $projects->firstWhere('id', $project->id) ?? abort(404);
    }
}
