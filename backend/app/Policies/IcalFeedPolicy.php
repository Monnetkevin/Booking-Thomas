<?php

namespace App\Policies;

use App\Models\IcalFeed;
use App\Models\User;

/** Synchronisation des calendriers : réservée aux administrateurs actifs. */
class IcalFeedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }

    public function view(User $user, IcalFeed $feed): bool
    {
        return $user->canOperate();
    }

    public function create(User $user): bool
    {
        return $user->canOperate();
    }

    public function update(User $user, IcalFeed $feed): bool
    {
        return $user->canOperate();
    }
}
