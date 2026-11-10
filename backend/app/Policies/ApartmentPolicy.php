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
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Apartment $apartment): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function publish(User $user, Apartment $apartment): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function reorder(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
