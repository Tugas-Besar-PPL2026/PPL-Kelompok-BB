<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\Notification;
use App\Models\UmkmProfile;
use Illuminate\Support\Facades\DB;

class UmkmVerificationService
{
    /** Menyimpan keputusan admin dan memberi tahu pemilik usaha. $decision: approve | reject. */
    public function decide(UmkmProfile $profile, string $decision, ?string $reason = null): void
    {
        DB::transaction(function () use ($profile, $decision, $reason) {
            if ($decision === 'approve') {
                $profile->update([
                    'verification_status' => VerificationStatus::Verified,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ]);
                $message = 'Profil usaha Anda telah terverifikasi. Anda dapat mengajukan proyek untuk dipublikasikan.';
            } else {
                $profile->update([
                    'verification_status' => VerificationStatus::Rejected,
                    'verified_at' => null,
                    'rejection_reason' => trim((string) $reason),
                ]);
                $message = 'Verifikasi profil usaha Anda ditolak. Lihat alasannya di halaman Profil Usaha.';
            }

            Notification::create([
                'user_id' => $profile->user_id,
                'type' => 'verification',
                'message' => $message,
                'related_type' => 'umkm_profile',
                'related_id' => $profile->id,
            ]);
        });
    }
}