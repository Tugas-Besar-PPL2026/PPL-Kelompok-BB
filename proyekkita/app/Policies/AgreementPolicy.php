<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;

/**
 * [PBI-24, PBI-25, PBI-26 / FR-12, FR-01] Hak akses kesepakatan pekerjaan.
 *
 *   Gate::authorize('manage', $agreement)  -> UMKM pemilik proyek (menyusun, mengirim, melihat detail)   PBI-24, PBI-26
 *   Gate::authorize('respond', $agreement) -> kandidat pemilik lamaran (menanggapi, melihat detail)      PBI-25, PBI-26
 *
 * Status kesepakatan tidak diperiksa di sini, tetapi di service/controller, agar pesannya spesifik.
 * Bukan pemilik -> 403. Policy ini ditemukan otomatis oleh Laravel karena namanya AgreementPolicy.
 */
class AgreementPolicy
{
    /** UMKM pemilik proyek: agreement -> application -> project -> umkm_profile.user_id = pengguna. */
    public function manage(User $user, Agreement $agreement): bool
    {
        return $user->isUmkm()
            && (int) $agreement->application?->project?->umkmProfile?->user_id === (int) $user->id;
    }

    /** Kandidat pemilik lamaran: agreement -> application.candidate_profile_id = profil pengguna. */
    public function respond(User $user, Agreement $agreement): bool
    {
        $profileId = $user->isKandidat() ? $user->candidateProfile?->id : null;

        return $profileId !== null
            && (int) $agreement->application?->candidate_profile_id === (int) $profileId;
    }
}
