<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\Notification;
use App\Models\UmkmProfile;
use Illuminate\Support\Facades\DB;

class UmkmVerificationService
{
    /**
     * Menyimpan keputusan admin dan memberi tahu pemilik usaha. $decision: approve | reject.
     *
     * Keputusan hanya berlaku untuk profil berstatus Menunggu Pemeriksaan. Status dibaca ulang dengan kunci baris
     * di dalam transaksi supaya dua admin yang memutuskan bersamaan tidak saling menimpa dan tidak menggandakan notifikasi.
     *
     * @return bool true bila keputusan tersimpan; false bila profil sudah diputuskan sebelumnya.
     */
    public function decide(UmkmProfile $profile, string $decision, ?string $reason = null): bool
    {
        $decided = DB::transaction(function () use ($profile, $decision, $reason) {
            $current = UmkmProfile::query()->whereKey($profile->getKey())->lockForUpdate()->first();

            if (! $current || $current->verification_status !== VerificationStatus::Pending) {
                return false;
            }

            if ($decision === 'approve') {
                $current->update([
                    'verification_status' => VerificationStatus::Verified,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ]);
                $message = 'Profil usaha Anda telah terverifikasi. Anda dapat mengajukan proyek untuk dipublikasikan.';
            } else {
                $current->update([
                    'verification_status' => VerificationStatus::Rejected,
                    'verified_at' => null,
                    'rejection_reason' => trim((string) $reason),
                ]);
                $message = 'Verifikasi profil usaha Anda ditolak. Lihat alasannya di halaman Profil Usaha.';
            }

            Notification::create([
                'user_id' => $current->user_id,
                'type' => 'verification',
                'message' => $message,
                'related_type' => 'umkm_profile',
                'related_id' => $current->id,
            ]);

            return true;
        });

        if ($decided) {
            $profile->refresh();
        }

        return $decided;
    }
}
