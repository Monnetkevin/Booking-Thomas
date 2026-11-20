<?php

namespace App\Exceptions\Availability;

use App\Exceptions\DomainException;

/** Le blocage a déjà été retiré (seconde demande simultanée). */
class BlockAlreadyRemovedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.block_already_removed', [], 'fr'));
    }

    protected function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'block_already_removed';
    }
}
