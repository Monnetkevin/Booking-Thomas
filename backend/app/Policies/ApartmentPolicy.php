<?php

namespace App\Policies;

use App\Models\Apartment;
use App\Models\User;

/**
 * Gestion des logements : réservée aux administrateurs actifs.
 */
class ApartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }

    public function view(User $user, Apartment $apartment): bool
    {
        return $user->canOperate();
    }

    public function create(User $user): bool
    {
        return $user->canOperate();
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $user->canOperate();
    }

    public function publish(User $user, Apartment $apartment): bool
    {
        return $user->canOperate();
    }

    public function reorder(User $user): bool
    {
        return $user->canOperate();
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $user->canOperate();
    }
}
