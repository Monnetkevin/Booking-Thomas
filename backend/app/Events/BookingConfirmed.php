<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/** Une réservation vient d'être confirmée (paiement reçu, ou saisie par l'hôte). */
class BookingConfirmed
{
    use Dispatchable;

    public function __construct(public readonly Booking $booking) {}
}
