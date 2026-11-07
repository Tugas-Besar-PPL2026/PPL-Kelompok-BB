<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateSelectionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $umkmProfile = $user->umkmProfile;

        if (!$umkmProfile) {
            abort(403, 'Profil UMKM tidak ditemukan.');
        }

        // Ambil semua proyek milik UMKM yang sedang login
        $projects = Project::where('umkm_profile_id', $umkmProfile->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($projects->isEmpty()) {
            return view('umkm.seleksi.index', [
                'projects' => $projects,
                'selectedProject' => null,
            ]);
        }

        // Penentuan proyek yang dilihat melalui input dropdown
        $projectId = $request->input('project_id');
        if ($projectId) {
            $selectedProject = $projects->where('id', $projectId)->first();
            if (!$selectedProject) {
                abort(403, 'Proyek tidak ditemukan atau Anda tidak memiliki akses ke proyek ini.');
            }
        } else {
            $selectedProject = $projects->first();
        }

        // Query dasar eager loading kandidat yang melamar proyek terpilih
        $query = Application::with(['candidateProfile.skills', 'candidateProfile.experiences'])
            ->where('project_id', $selectedProject->id);

        // Menghitung statistik pelamar untuk kartu statistik dan lencana tab
        $stats = [
            'total' => (clone $query)->count(),
            'menunggu' => (clone $query)->where('status', 'pending')->count(),
            'terpilih_sementara' => (clone $query)->where('status', 'shortlisted')->count(),
            'diterima' => (clone $query)->where('status', 'accepted')->count(),
            'ditolak' => (clone $query)->where('status', 'rejected')->count(),
        ];

        // Filter berdasarkan status tab
        $statusFilter = $request->input('status', 'all');
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $applications = $query->latest()->get();

        return view('umkm.seleksi.index', compact('projects', 'selectedProject', 'applications', 'stats', 'statusFilter'));
    }

    public function show($projectId, $applicationId)
    {
        $user = Auth::user();
        $umkmProfile = $user->umkmProfile;

        // Memastikan proyek tersebut memang milik UMKM yang sedang login (Authorization)
        $project = Project::with('skills')
            ->where('id', $projectId)
            ->where('umkm_profile_id', $umkmProfile->id)
            ->firstOrFail();

        // Mengambil detail lengkap lamaran beserta seluruh riwayat statusnya
        $application = Application::with([
            'candidateProfile.skills',
            'candidateProfile.experiences',
            'candidateProfile.portfolios',
            'statusHistories' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])->where('id', $applicationId)
          ->where('project_id', $project->id)
          ->firstOrFail();

        return view('umkm.seleksi.show', compact('project', 'application'));
    }
}