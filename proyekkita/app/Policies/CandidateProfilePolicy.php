<?php

namespace App\Policies;

use App\Models\CandidateProfile;
use App\Models\User;

class CandidateProfilePolicy
{
    public function update(User $user, CandidateProfile $profile): bool
    {
        return $user->isKandidat() && (int) $profile->user_id === $user->id;
    }
}
