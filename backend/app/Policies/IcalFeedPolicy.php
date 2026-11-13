<?php

namespace App\Policies;

use App\Models\IcalFeed;
use App\Models\User;

/** Synchronisation des calendriers : réservée aux administrateurs actifs. */
class IcalFeedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, IcalFeed $feed): bool
    {
        return $user->isActiveAdmin();
    }
}
