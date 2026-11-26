<?php

namespace App\Policies;

use App\Models\AvailabilityBlock;
use App\Models\User;

/** Blocages de dates : administrateur actif ; planning du ménage : aussi le compte ménage. */
class AvailabilityBlockPolicy
{
    /** Planning de tous les logements (noms des voyageurs) : administrateur actif. */
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /** Planning du ménage (sans nom, coordonnées ni montant) : administrateur ou ménage, compte actif. */
    public function viewCleaning(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isHousekeeping());
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, AvailabilityBlock $block): bool
    {
        return $user->isActiveAdmin();
    }
}
