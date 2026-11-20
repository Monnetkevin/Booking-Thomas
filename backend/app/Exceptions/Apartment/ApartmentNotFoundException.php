<?php

namespace App\Exceptions\Apartment;

use App\Exceptions\DomainException;

/** Logement inconnu ou supprimé entre la validation et l'enregistrement. */
class ApartmentNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('apartments.errors.not_found', [], 'fr'));
    }

    protected function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'apartment_not_found';
    }
}
