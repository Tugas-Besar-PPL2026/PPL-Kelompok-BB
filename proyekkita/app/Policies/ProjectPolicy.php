<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
   public function update(User $user, Project $project): bool
    {
        return $user->isUmkm() && (int) $project->umkmProfile?->user_id === $user->id;
    }

    /** Daftar pelamar (Seleksi Kandidat) hanya boleh dilihat UMKM pemilik proyek. */
    public function viewApplicants(User $user, Project $project): bool
    {
        return $user->isUmkm() && (int) $project->umkmProfile?->user_id === $user->id;
    }

    /**
     * [PBI-08 #7] Mengajukan proyek untuk dipublikasikan (moderasi admin) hanya boleh dilakukan pemilik proyek
     * yang akun UMKM-nya Terverifikasi. Menyimpan draf tetap diizinkan (lihat 'update').
     * Dipanggil oleh alur pengajuan proyek: Gate::authorize('submit', $project).
     */
    public function submit(User $user, Project $project): bool
    {
        return $this->update($user, $project) && (bool) $project->umkmProfile?->isVerified();
    }
}
