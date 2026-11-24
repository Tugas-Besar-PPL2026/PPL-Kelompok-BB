<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\Submission;
use App\Models\User;

/**
 * [PBI-31 / FR-14, NF-01, NF-05] Hak akses penyerahan hasil pekerjaan.
 *
 * Dipakai ulang oleh PBI-32 (UMKM meninjau dan mengunduh berkas) dan PBI-33 (kandidat mengirim versi perbaikan).
 * Pemakaian dengan kesepakatan sebagai konteks:
 *   Gate::allows('viewAny', [Submission::class, $agreement])  -> kandidat pemilik melihat Riwayat Versi
 *   Gate::allows('create', [Submission::class, $agreement])   -> kandidat pemilik mengirim penyerahan
 *   Gate::allows('download', $submission)                     -> kandidat pemilik atau UMKM pemilik proyek
 *   Gate::allows('review', [Submission::class, $agreement])   -> UMKM pemilik proyek meninjau hasil (PBI-32)
 * Status kesepakatan/penyerahan TIDAK diperiksa di sini (lihat SubmissionService) agar pesan penolakannya spesifik.
 */
class SubmissionPolicy
{
    public function viewAny(User $user, Agreement $agreement): bool
    {
        return $this->candidateOwns($user, $agreement);
    }

    public function create(User $user, Agreement $agreement): bool
    {
        return $this->candidateOwns($user, $agreement);
    }

    /** [PBI-32] UMKM pemilik proyek pada kesepakatan meninjau hasil pekerjaan. */
    public function review(User $user, Agreement $agreement): bool
    {
        return $user->isUmkm() && $this->umkmOwns($user, $agreement);
    }

    public function download(User $user, Submission $submission): bool
    {
        $agreement = $submission->agreement;

        if (! $agreement) {
            return false;
        }

        if ($user->isKandidat()) {
            return $this->candidateOwns($user, $agreement);
        }

        if ($user->isUmkm()) {
            return $this->umkmOwns($user, $agreement);
        }

        return false;
    }

    /** Kesepakatan milik kandidat: agreements.application -> applications.candidate_profile_id = profil pengguna. */
    public function candidateOwns(User $user, Agreement $agreement): bool
    {
        $profileId = $user->isKandidat() ? $user->candidateProfile?->id : null;

        return $profileId !== null
            && (int) $agreement->application?->candidate_profile_id === (int) $profileId;
    }

    /** Kesepakatan pada proyek milik UMKM: application -> project -> umkm_profile.user_id = pengguna. */
    public function umkmOwns(User $user, Agreement $agreement): bool
    {
        return (int) $agreement->application?->project?->umkmProfile?->user_id === (int) $user->id;
    }
}
