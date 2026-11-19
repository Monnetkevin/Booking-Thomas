<?php

namespace App\Exceptions\Ical;

use App\Enums\IcalProvider;
use App\Exceptions\DomainException;

/** Un logement n'a qu'un calendrier par plateforme. */
class IcalFeedAlreadyExistsException extends DomainException
{
    public function __construct(IcalProvider $provider)
    {
        parent::__construct(__('ical.errors.already_exists', ['provider' => $provider->label()], 'fr'));
    }

    protected function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ical_feed_already_exists';
    }
}
