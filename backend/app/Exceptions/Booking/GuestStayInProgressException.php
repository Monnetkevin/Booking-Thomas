<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/** Le voyageur a un séjour en cours ou à venir : ses coordonnées servent encore au contrat. */
class GuestStayInProgressException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.guest_stay_in_progress', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'guest_stay_in_progress';
    }
}
