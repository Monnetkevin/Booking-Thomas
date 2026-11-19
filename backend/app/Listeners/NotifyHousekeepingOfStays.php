<?php

namespace App\Listeners;

use App\Events\StayScheduleChanged;
use App\Services\Housekeeping\HousekeepingNotifier;

/** Séjour importé d'une plateforme ajouté, annulé ou déplacé : le ménage est prévenu. */
class NotifyHousekeepingOfStays
{
    public function __construct(private readonly HousekeepingNotifier $housekeeping) {}

    public function handle(StayScheduleChanged $event): void
    {
        $this->housekeeping->stayChanged($event->change);
    }
}
