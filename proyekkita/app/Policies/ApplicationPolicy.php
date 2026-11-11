<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * Hak akses lamaran untuk Seleksi Kandidat (PBI-19).
 * Hanya UMKM pemilik proyek yang boleh melihat pelamar dan detail lamaran pada proyeknya.
 */
class ApplicationPolicy
{
    /** Membuka halaman Seleksi Kandidat: hanya akun UMKM. */
    public function viewAny(User $user): bool
    {
        return $user->isUmkm();
    }

    /** Melihat satu lamaran: akun UMKM yang memiliki proyek tempat lamaran itu diajukan. */
    public function view(User $user, Application $application): bool
    {
        return $user->isUmkm()
            && (int) $application->project?->umkmProfile?->user_id === $user->id;
    }
}
