<?php

namespace App\Exceptions\Ical;

use App\Exceptions\DomainException;

/** Synchronisation demandée sur un calendrier désactivé : ses nuits restent bloquées telles quelles. */
class IcalFeedInactiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('ical.errors.inactive', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ical_feed_inactive';
    }
}
