<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Exceptions\SelectionNotAllowedException;
use App\Http\Requests\SelectionDecisionRequest;
use App\Models\Portfolio;
use App\Models\Project;
use App\Services\CandidateAcceptanceService;
use App\Services\CandidateDecisionService;
use App\Services\CandidatePortfolioService;
use App\Services\CandidateSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Seleksi Kandidat sisi UMKM (FR-11, PBI-19): daftar pelamar per proyek dan detail pelamar.
 * Halaman ini hanya menampilkan data. Keputusan seleksi (PBI-20 dan PBI-21) belum dikerjakan di sini.
 */
class CandidateSelectionController extends Controller
{
    public function __construct(
        private CandidateSelectionService $selection,
        private CandidateDecisionService $decisions,
        private CandidateAcceptanceService $acceptance,
        private CandidatePortfolioService $portfolios,
    ) {}

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
            // Info berkas portofolio (null bila berkas tidak ada di penyimpanan), diindeks per id portofolio.
            'files' => $profile->portfolios->mapWithKeys(fn (Portfolio $p) => [$p->id => $this->portfolios->locateFile($p)])->all(),
        ]);
    }

    /** Memproses keputusan seleksi: shortlist / reject (PBI-20) atau accept (PBI-21). */
    public function decide(SelectionDecisionRequest $request, Application $application)
    {
        Gate::authorize('decide', $application);

        $note = $request->note();
        $back = redirect()->route('umkm.seleksi.show', array_filter([
            'application' => $application->id,
            'status' => $request->query('status'),
        ]));

        try {
            match ($request->validated('keputusan')) {
                'shortlist' => $this->decisions->shortlist($application, $note),
                'reject' => $this->decisions->reject($application, $note),
                'accept' => $this->acceptance->accept($application, $note),
            };
        } catch (SelectionNotAllowedException $e) {
            return $back->with('error', $e->getMessage())->withInput();
        }

        return $back->with('success', match ($request->validated('keputusan')) {
            'shortlist' => 'Pelamar dimasukkan ke daftar terpilih sementara.',
            'reject' => 'Pelamar ditolak dan catatan penolakan tersimpan.',
            'accept' => 'Pelamar berhasil diterima.',
        });
    }

    /** Membuka dokumen portofolio pelamar. Hanya untuk UMKM pemilik proyek, dan dokumen harus milik pelamar itu. */
    public function portfolioFile(Application $application, Portfolio $portfolio)
    {
        Gate::authorize('view', $application);
        abort_unless((int) $portfolio->candidate_profile_id === (int) $application->candidate_profile_id, 404);

        $file = $this->portfolios->locateFile($portfolio);
        abort_if($file === null, 404, 'Berkas portofolio tidak ditemukan.');

        $disk = Storage::disk($file['disk']);
        $name = basename($file['path']);
        $headers = ['X-Content-Type-Options' => 'nosniff'];
        $mime = $disk->mimeType($file['path']) ?: '';

        // Hanya PDF dan gambar yang ditampilkan di browser. Tipe lain diunduh agar tidak dieksekusi.
        if ($mime === 'application/pdf' || in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return $disk->response($file['path'], $name, $headers);
        }

        return $disk->download($file['path'], $name, $headers);
    }

    /** Nilai ?status= yang tidak dikenal diperlakukan sebagai "Semua"; status Dibatalkan bukan tab. */
    private function statusFromRequest(Request $request): ?ApplicationStatus
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        return in_array($status, CandidateSelectionService::TABS, true) ? $status : null;
    }
}
