<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ApplicationService;
use App\Services\ProjectDiscoveryService;
use Illuminate\Http\Request;

/**
 * [PBI-14 / FR-08] Daftar proyek yang terbuka dan halaman Detail Proyek untuk kandidat.
 * Halaman detail juga memuat kartu Ajukan Lamaran (PBI-16): data kartunya diambil dari
 * ApplicationService, sedangkan proses pengiriman ada di ApplicationController.
 */
class ProjectDiscoveryController extends Controller
{
    public function __construct(
        private ProjectDiscoveryService $discovery,
        private ApplicationService $applications,
    ) {}

    public function index(Request $request)
    {
        $this->ensureKandidat($request);

        return view('kandidat.proyek.index', [
            'projects' => $this->discovery->listOpen(),
            'discovery' => $this->discovery,
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
