<?php

namespace App\Listeners;

use App\Events\IcalConflictDetected;
use App\Mail\IcalConflictAlert;
use App\Services\Notifications\HostNotifier;

/** Alerte immédiate de l'hôte : il annule l'une des deux réservations sans attendre. */
class SendIcalConflictAlert
{
    public function __construct(private readonly HostNotifier $host) {}

    public function handle(IcalConflictDetected $event): void
    {
        $this->host->notify(new IcalConflictAlert($event->block, $event->bookings));
    }
}
