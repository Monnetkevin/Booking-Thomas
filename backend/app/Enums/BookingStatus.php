<?php

namespace App\Enums;

/**
 * pending → confirmed | expired ; confirmed → completed | cancelled.
 * Le paiement est intégral à la réservation : il n'y a pas de statut « paid » distinct.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /** Statuts dont les nuits restent occupées. */
    public function holdsNights(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Completed], true);
    }
}
