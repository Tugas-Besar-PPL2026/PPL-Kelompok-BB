<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
   public function update(User $user, Project $project): bool
    {
        return $user->isUmkm() && (int) $project->umkmProfile?->user_id === $user->id;
    }

    public function viewApplicants(User $user, Project $project): bool
    {
        return $user->isUmkm() && (int) $project->umkmProfile?->user_id === $user->id;
    }
}