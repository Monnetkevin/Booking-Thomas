<?php

namespace App\Policies;

use App\Models\Extra;
use App\Models\User;

/** Catalogue des options (prix) : administrateur actif seulement. */
class ExtraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Extra $extra): bool
    {
        return $user->isActiveAdmin();
    }
}
