<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\UmkmProfile;
use App\Models\User;

class UmkmProfileService
{
    /**
     * Menyimpan profil usaha milik $user.
     * Aturan FR-02: profil Terverifikasi yang mengubah nama usaha / NIB / tautan kembali ke Menunggu Pemeriksaan.
     * Profil Ditolak yang diperbaiki otomatis diajukan ulang (Menunggu Pemeriksaan).
     *
     * @return array{0: UmkmProfile, 1: bool} profil dan penanda apakah status direset
     */
    public function save(User $user, array $data): array
    {
        $profile = $user->umkmProfile ?? new UmkmProfile([
            'user_id' => $user->id,
            'verification_status' => VerificationStatus::Pending,
        ]);

        $profile->fill($data);
        $reset = false;

        if ($profile->exists) {
            $status = $profile->verification_status;
            $verifiedAndAffected = $status === VerificationStatus::Verified
                && $profile->isDirty(UmkmProfile::VERIFICATION_FIELDS);
            $rejectedAndEdited = $status === VerificationStatus::Rejected && $profile->isDirty();

            if ($verifiedAndAffected || $rejectedAndEdited) {
                $profile->verification_status = VerificationStatus::Pending;
                $profile->verified_at = null;
                $profile->rejection_reason = null;
                $reset = true;
            }
        }

        $profile->save();

        return [$profile, $reset];
    }
}