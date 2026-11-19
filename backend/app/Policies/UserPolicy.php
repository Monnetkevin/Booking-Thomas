<?php

namespace App\Policies;

use App\Models\User;

/**
 * Comptes du back-office : gérés par un administrateur actif. Chacun règle ses propres
 * préférences de notification.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, User $account): bool
    {
        return $user->isActiveAdmin();
    }

    public function managePreferences(User $user, User $account): bool
    {
        return $user->is_active && $user->is($account);
    }
}
