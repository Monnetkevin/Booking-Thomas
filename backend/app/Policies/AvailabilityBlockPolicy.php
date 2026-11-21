<?php

namespace App\Policies;

use App\Models\AvailabilityBlock;
use App\Models\User;

/** Blocages de dates : administrateur actif seulement. */
class AvailabilityBlockPolicy
{
    /** Planning de tous les logements (noms des voyageurs) : administrateur actif. */
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }

    public function create(User $user): bool
    {
        return $user->canOperate();
    }

    public function delete(User $user, AvailabilityBlock $block): bool
    {
        return $user->canOperate();
    }
}
