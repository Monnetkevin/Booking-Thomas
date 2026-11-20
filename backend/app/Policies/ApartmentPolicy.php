<?php

namespace App\Policies;

use App\Enums\ApartmentStatus;
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

    /**
     * Modifier les prix d'un logement (frais de ménage) : administrateur seulement, comme les
     * tarifs. Le gestionnaire les voit (ils sont publics sur le site) et modifie le reste.
     */
    public function managePricing(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Aperçu du prix d'un séjour (réservation saisie à la main) : l'administrateur pour tout
     * logement ; le gestionnaire seulement pour un logement publié (prix déjà publics).
     */
    public function previewQuote(User $user, Apartment $apartment): bool
    {
        return $user->isActiveAdmin() || ($user->canOperate() && $apartment->status === ApartmentStatus::Published);
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
