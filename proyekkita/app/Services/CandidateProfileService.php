<?php

namespace App\Services;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CandidateProfileService
{
    /** Membuat profil kandidat bila belum ada, atau memperbaruinya. */
    public function save(User $user, array $data, ?UploadedFile $photo = null): CandidateProfile
    {
        $profile = $user->candidateProfile ?? new CandidateProfile(['user_id' => $user->id]);

        // Simpan lokasi foto lama sebelum perubahan.
        $oldPhoto = $profile->photo;

        $profile->fill($data);

        if ($photo) {
            // Simpan foto baru terlebih dahulu.
            $newPhoto = $photo->store('candidate-photos', 'public');

            if ($newPhoto === false) {
                throw new \RuntimeException('Gagal menyimpan foto profil.');
            }

            $profile->photo = $newPhoto;
        }

        // Simpan profil.
        $profile->save();

        // Hapus foto lama hanya setelah penyimpanan profil berhasil.
        if ($photo && $oldPhoto && $oldPhoto !== $profile->photo) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return $profile;
    }
}
