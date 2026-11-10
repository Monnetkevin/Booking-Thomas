<?php

namespace App\Policies;

use App\Models\RatePeriod;
use App\Models\User;

/** Tarifs : réservés aux administrateurs actifs. */
class RatePeriodPolicy
{
    public function update(User $user, RatePeriod $period): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, RatePeriod $period): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
