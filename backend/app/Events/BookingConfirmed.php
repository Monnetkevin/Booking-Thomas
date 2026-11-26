<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Une réservation vient d'être confirmée (paiement reçu, ou saisie par l'hôte). Diffusé après
 * la transaction : une panne d'un listener (e-mails, notifications) n'annule jamais la confirmation.
 */
class BookingConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  bool  $notifyGuest  envoyer la confirmation au voyageur (réservation saisie à la
     *                             main : au choix de l'hôte)
     */
    public function __construct(public readonly Booking $booking, public readonly bool $notifyGuest = true) {}
}
