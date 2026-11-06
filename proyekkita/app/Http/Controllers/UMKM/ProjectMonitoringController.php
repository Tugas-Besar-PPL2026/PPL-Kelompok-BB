<?php

namespace App\Http\Controllers\UMKM;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ProjectMonitoringController extends Controller
{
    /**
     * Menampilkan daftar proyek milik UMKM
     * beserta status dan hasil moderasinya.
     */
    public function index()
    {
        $umkmProfile = auth()->user()->umkmProfile;

        abort_if(!$umkmProfile, 403, 'Profil UMKM tidak ditemukan.');

        $projects = Project::with([
            'category',
            'moderations'
        ])
            ->where('umkm_profile_id', $umkmProfile->id)
            ->orderByDesc('id')
            ->get();

        return view('umkm.projects.monitoring', compact('projects'));
    }

    /**
     * Menampilkan detail proyek dan riwayat moderasi.
     */
    public function show($id)
    {
        $umkmProfile = auth()->user()->umkmProfile;

        abort_if(!$umkmProfile, 403, 'Profil UMKM tidak ditemukan.');

        $project = Project::with([
            'category',
            'moderations.admin'
        ])
            ->where('umkm_profile_id', $umkmProfile->id)
            ->findOrFail($id);

        return view('umkm.projects.monitoring-detail', compact('project'));
    }
}