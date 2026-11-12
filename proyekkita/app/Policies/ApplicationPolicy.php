<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * Hak akses atas lamaran. Detail pelamar hanya boleh dibuka oleh UMKM pemilik proyek yang dilamar.
 */
class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        return $user->isUmkm()
            && (int) $application->project?->umkmProfile?->user_id === $user->id;
    }
}
