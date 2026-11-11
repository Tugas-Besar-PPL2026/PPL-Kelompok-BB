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
}