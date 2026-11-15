<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\UmkmProfile;
use App\Models\User;

class UmkmProfileService
{
    /**
     * Menyimpan profil usaha milik $user (selalu milik pengguna itu sendiri, tidak pernah profil orang lain).
     *
     * Aturan FR-02:
     * - Profil Terverifikasi yang mengubah nama usaha / NIB / tautan kembali ke Menunggu Pemeriksaan.
     *   Mengubah kontak, kota, alamat, bidang, atau deskripsi saja tidak mengubah status.
     * - Profil Ditolak yang diperbaiki (ada data yang berubah) otomatis diajukan ulang ke Menunggu Pemeriksaan
     *   dan alasan penolakan lama dibersihkan.
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