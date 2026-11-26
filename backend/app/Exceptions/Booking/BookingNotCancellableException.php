<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/** Seule une réservation confirmée s'annule ; une réservation en attente de paiement expire. */
class BookingNotCancellableException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.not_cancellable', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'booking_not_cancellable';
    }
}
