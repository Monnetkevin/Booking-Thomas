<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/** Pas de confirmation à renvoyer pour une réservation qui n'est pas confirmée. */
class BookingNotConfirmedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.not_confirmed', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'booking_not_confirmed';
    }
}
