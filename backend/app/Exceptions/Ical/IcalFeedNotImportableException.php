<?php

namespace App\Exceptions\Ical;

use App\Exceptions\DomainException;

/** Synchronisation demandée sur un calendrier sans URL d'import. */
class IcalFeedNotImportableException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('ical.errors.no_import_url', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ical_feed_not_importable';
    }
}
