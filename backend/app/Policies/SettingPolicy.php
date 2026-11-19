<?php

namespace App\Policies;

use App\Models\User;

/** Réglages du site : administrateur actif seulement. */
class SettingPolicy
{
    public function view(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
