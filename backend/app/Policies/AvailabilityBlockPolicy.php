<?php

namespace App\Policies;

use App\Models\AvailabilityBlock;
use App\Models\User;

/** Blocages de dates : administrateur actif seulement. */
class AvailabilityBlockPolicy
{
    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, AvailabilityBlock $block): bool
    {
        return $user->isActiveAdmin();
    }
}
