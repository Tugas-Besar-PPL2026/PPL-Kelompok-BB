<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->isKandidat()
            && (int) $portfolio->candidateProfile?->user_id === $user->id;
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
