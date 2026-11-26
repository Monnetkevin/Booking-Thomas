<?php

namespace App\Exceptions\Extra;

use App\Exceptions\DomainException;

/** Option inconnue, désactivée, pas proposée dans ce logement, trop tardive ou en trop grand nombre. */
class ExtraNotAvailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('extras.not_available'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'extra_not_available';
    }
}
