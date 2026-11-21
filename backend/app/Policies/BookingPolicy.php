<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/** Réservations (coordonnées, montants) : administrateur actif seulement. */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->canOperate();
    }

    public function create(User $user): bool
    {
        return $user->canOperate();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->canOperate();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->canOperate();
    }
}
