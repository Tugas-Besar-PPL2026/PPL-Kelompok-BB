<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Services\ProjectMonitoringService;
use Illuminate\Http\Request;

/**
 * [PBI-13 / FR-06, FR-07] Proyek Saya: UMKM memantau daftar proyek, status, dan hasil moderasi.
 * Dipisah dari ProjectController (PBI-09/10/11) supaya dua PBI tidak mengedit file yang sama.
 */
class ProjectMonitoringController extends Controller
{
    public function __construct(private ProjectMonitoringService $monitoring) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isUmkm(), 403, 'Halaman ini khusus UMKM.');

        // Profil dijamin ada oleh middleware umkm.profile (belum ada: diarahkan ke halaman profil, bukan 403).
        $umkm = $user->umkmProfile;

        // Nilai status yang tidak dikenal dianggap tidak ada filter (tampil Semua).
        $status = ProjectStatus::tryFrom($request->string('status')->toString());

        return view('umkm.proyek.index', [
            'projects' => $this->monitoring->projects($umkm, $status)->withQueryString(),
            'monitoring' => $this->monitoring,
            'status' => $status,
            'counts' => $this->monitoring->statusCounts($umkm),
            'totalApplicants' => $this->monitoring->totalApplicants($umkm),
            'tabs' => ProjectMonitoringService::TABS,
        ]);
    }
}
