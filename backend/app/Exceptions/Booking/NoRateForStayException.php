<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/** Aucun tarif ne couvre le séjour et l'hôte n'a pas fixé de total. */
class NoRateForStayException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.no_rate', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'no_rate';
    }
}
