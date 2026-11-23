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
        return $user->isActiveAdmin();
    }

    public function view(User $user, Apartment $apartment): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $user->isActiveAdmin();
    }

    public function publish(User $user, Apartment $apartment): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Modifier les prix d'un logement (frais de ménage) : administrateur seulement, comme les
     * tarifs (droit gardé à part si un rôle plus restreint revient).
     */
    public function managePricing(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $user->isActiveAdmin();
    }
}
