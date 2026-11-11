<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Project;
use App\Services\CandidateSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Seleksi Kandidat sisi UMKM (FR-11, PBI-19): daftar pelamar per proyek dan detail pelamar.
 * Halaman ini hanya menampilkan data. Keputusan seleksi (PBI-20 dan PBI-21) belum dikerjakan di sini.
 */
class CandidateSelectionController extends Controller
{
    public function __construct(private CandidateSelectionService $selection) {}

    /** Daftar pelamar untuk satu proyek, dengan statistik, kuota, dan filter status. */
    public function index(Request $request)
    {
        abort_unless($request->user()->isUmkm(), 403, 'Halaman ini khusus UMKM.');

        // Hanya proyek milik UMKM yang sedang login yang pernah masuk ke daftar ini.
        $projects = $this->selection->projectsFor($request->user());

        if ($projects->isEmpty()) {
            return view('umkm.seleksi.index', ['projects' => $projects, 'project' => null]);
        }

        $requestedId = $request->integer('proyek');
        if ($requestedId > 0) {
            $project = Project::findOrFail($requestedId);
            Gate::authorize('viewApplicants', $project);
            abort_unless($projects->contains('id', $project->id), 404);
        } else {
            $project = $projects->first();
        }

        $status = $this->statusFromRequest($request);
        $counts = $this->selection->statusCounts($project);
        $applicants = $this->selection->applicants($project, $status)->withQueryString();
        $reputation = $this->selection->reputation($applicants->getCollection()->pluck('candidateProfile'));

        return view('umkm.seleksi.index', [
            'projects' => $projects,
            'project' => $project,
            'status' => $status,
            'counts' => $counts,
            'quota' => $this->selection->quota($project, $counts[ApplicationStatus::Accepted->value]),
            'applicants' => $applicants,
            'reputation' => $reputation,
            'tabs' => CandidateSelectionService::TABS,
        ]);
    }

    /** Detail satu pelamar: profil, pesan lamaran, keterampilan, pengalaman, portofolio, dan riwayat status. */
    public function show(Request $request, Application $application)
    {
        Gate::authorize('view', $application);

        $application->load([
            'project.skills',
            'candidateProfile.skills' => fn ($query) => $query->orderBy('skills.name'),
            'candidateProfile.experiences',
            'candidateProfile.portfolios',
            'statusHistories',
        ]);

        $project = $application->project;
        $profile = $application->candidateProfile;
        $counts = $this->selection->statusCounts($project);
        $status = $this->statusFromRequest($request);

        return view('umkm.seleksi.show', [
            'application' => $application,
            'project' => $project,
            'profile' => $profile,
            'reputation' => $this->selection->reputation(collect([$profile]))[$profile->id] ?? ['rating' => null, 'evaluations' => 0, 'completed' => 0],
            'quota' => $this->selection->quota($project, $counts[ApplicationStatus::Accepted->value]),
            'requiredSkillIds' => $project->skills->pluck('id')->all(),
            'listStatus' => $status,
        ]);
    }

    /** Nilai ?status= yang tidak dikenal diperlakukan sebagai "Semua"; status Dibatalkan bukan tab. */
    private function statusFromRequest(Request $request): ?ApplicationStatus
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        return in_array($status, CandidateSelectionService::TABS, true) ? $status : null;
    }
}
