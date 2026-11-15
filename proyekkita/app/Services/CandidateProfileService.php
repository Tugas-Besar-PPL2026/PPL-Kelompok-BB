<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CandidateProfileService
{
    /** Membuat profil kandidat bila belum ada, atau memperbaruinya. Foto lama dihapus saat diganti. */
    public function save(User $user, array $data, ?UploadedFile $photo = null): CandidateProfile
    {
        $profile = $user->candidateProfile ?? new CandidateProfile(['user_id' => $user->id]);
        $profile->fill($data);

        if ($photo) {
            if ($profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }
            $profile->photo = $photo->store('candidate-photos', 'public');
        }

        $profile->save();

        return $profile;
    }
}