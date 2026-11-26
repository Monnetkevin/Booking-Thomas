<?php

namespace App\Exceptions\Availability;

use App\Exceptions\DomainException;

/** Seul un blocage manuel se retire ; une réservation s'annule, un séjour importé suit sa plateforme. */
class BlockNotRemovableException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.block_not_removable', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'block_not_removable';
    }
}
