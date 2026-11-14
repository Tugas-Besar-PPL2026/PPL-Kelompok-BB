<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Exceptions\ApplicationNotAllowedException;
use App\Http\Requests\ApplicationRequest;
use App\Models\Project;
use App\Services\ApplicationService;
use App\Services\ProjectDiscoveryService;
use Illuminate\Http\RedirectResponse;

/**
 * [PBI-16 / FR-09] Pengajuan lamaran kandidat pada proyek.
 * Pembatalan dan pemantauan lamaran (FR-10) bukan bagian dari controller ini.
 */
class ApplicationController extends Controller
{
    public function __construct(private ApplicationService $applications) {}

    public function store(ApplicationRequest $request, Project $project): RedirectResponse
    {
        // Proyek draf/moderasi/ditolak tidak boleh diketahui kandidat.
        abort_unless(in_array($project->status, ProjectDiscoveryService::VIEWABLE, true), 404);

        $back = redirect()->route('kandidat.proyek.show', $project);

        try {
            $this->applications->submit($request->user(), $project, $request->validated('message'));
        } catch (ApplicationNotAllowedException $e) {
            return $back->with('error', $e->getMessage())->withInput();
        }

        return $back->with('success', 'Lamaran berhasil dikirim dan berstatus ' . ApplicationStatus::Pending->label() . '.');
    }
}
