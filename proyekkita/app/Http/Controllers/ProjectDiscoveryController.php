<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectSearchRequest;
use App\Models\Project;
use App\Services\ApplicationService;
use App\Services\ProjectDiscoveryService;
use Illuminate\Http\Request;

/**
 * [PBI-14 + PBI-15 / FR-08] Eksplor Proyek untuk kandidat: daftar proyek terbuka dengan pencarian, filter,
 * dan pengurutan, serta halaman Detail Proyek.
 * Halaman detail juga memuat kartu Ajukan Lamaran (PBI-16): data kartunya diambil dari
 * ApplicationService, sedangkan proses pengiriman ada di ApplicationController.
 */
class ProjectDiscoveryController extends Controller
{
    public function __construct(
        private ProjectDiscoveryService $discovery,
        private ApplicationService $applications,
    ) {}

    /** Peran kandidat dan validasi parameter ditangani ProjectSearchRequest. */
    public function index(ProjectSearchRequest $request)
    {
        $filters = $request->filters();

        return view('kandidat.proyek.index', [
            // withQueryString: tautan halaman berikutnya membawa kata kunci, filter, dan urutan.
            'projects' => $this->discovery->listOpen($filters)->withQueryString(),
            'discovery' => $this->discovery,
            'filters' => $filters,
            'filterCount' => $this->discovery->activeFilterCount($filters),
            'categories' => $this->discovery->categoryOptions(),
            'skills' => $this->discovery->skillOptions(),
            'sorts' => ProjectDiscoveryService::SORTS,
        ]);
    }

    public function show(Request $request, Project $project)
    {
        $this->ensureKandidat($request);
        abort_unless(in_array($project->status, ProjectDiscoveryService::VIEWABLE, true), 404);

        $project = $this->discovery->loadDetail($project);

        return view('kandidat.proyek.show', [
            'project' => $project,
            'outputs' => $this->discovery->expectedOutputItems($project),
            'deadlineNote' => $this->discovery->deadlineNote($project),
            'remainingQuota' => $this->discovery->remainingQuota($project),
            // [PBI-16] keadaan kartu Ajukan Lamaran
            'apply' => $this->applications->formState($request->user(), $project),
        ]);
    }

    /** Halaman ini khusus kandidat. Middleware peran belum tersedia di repository (FR-01), jadi dicek di sini. */
    private function ensureKandidat(Request $request): void
    {
        abort_unless($request->user()?->isKandidat(), 403, 'Halaman ini hanya untuk kandidat.');
    }
}
