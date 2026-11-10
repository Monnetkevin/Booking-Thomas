<?php

namespace App\Exceptions\Apartment;

use App\Exceptions\DomainException;

class ApartmentHasUpcomingStaysException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('apartments.errors.upcoming_stays'));
    }

    protected function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'apartment_has_upcoming_stays';
    }
}
