<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/** Rien ne peut être envoyé au voyageur : son e-mail n'a pas été saisi. */
class GuestEmailMissingException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.no_guest_email', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'guest_email_missing';
    }
}
