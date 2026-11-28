<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/** Réservations (coordonnées, montants) : administrateur actif seulement. */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->isActiveAdmin();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->isActiveAdmin();
    }

    /** Export et anonymisation des données du voyageur (RGPD). */
    public function managePersonalData(User $user, Booking $booking): bool
    {
        return $user->isActiveAdmin();
    }
}
