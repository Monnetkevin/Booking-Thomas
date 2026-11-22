<?php

namespace App\Policies;

use App\Models\User;

/** Revenus des plateformes : saisie réservée à l'administrateur actif. */
class PlatformMonthlyRevenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
