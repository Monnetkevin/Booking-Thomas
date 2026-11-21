<?php

namespace App\Policies;

use App\Models\User;

/** Revenus des plateformes : import réservé à l'administrateur. */
class PlatformImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
