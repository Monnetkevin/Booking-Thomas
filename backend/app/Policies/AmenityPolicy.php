<?php

namespace App\Policies;

use App\Models\User;

class AmenityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
