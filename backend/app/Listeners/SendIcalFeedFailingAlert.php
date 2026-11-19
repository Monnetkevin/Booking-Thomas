<?php

namespace App\Listeners;

use App\Events\IcalFeedFailing;
use App\Mail\IcalFeedFailingAlert;
use App\Services\Notifications\HostNotifier;

/** L'hôte vérifie l'URL d'import ou synchronise à la main depuis le back-office. */
class SendIcalFeedFailingAlert
{
    public function __construct(private readonly HostNotifier $host) {}

    public function handle(IcalFeedFailing $event): void
    {
        $this->host->notify(new IcalFeedFailingAlert($event->feed));
    }
}
