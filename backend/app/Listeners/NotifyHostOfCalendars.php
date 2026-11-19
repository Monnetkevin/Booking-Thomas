<?php

namespace App\Listeners;

use App\Events\IcalConflictDetected;
use App\Events\IcalEmptyCalendarRefused;
use App\Events\IcalFeedFailing;
use App\Events\IcalFeedRecovered;
use App\Notifications\Host\IcalConflictNotification;
use App\Notifications\Host\IcalEmptyCalendarRefusedNotification;
use App\Notifications\Host\IcalFeedFailingNotification;
use App\Notifications\Host\IcalFeedRecoveredNotification;
use App\Services\Notifications\NotificationDispatcher;

/** Incidents des calendriers des plateformes, signalés à l'hôte (back-office et e-mail). */
class NotifyHostOfCalendars
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    /** Deux voyageurs sur les mêmes nuits : l'hôte annule l'une des réservations sans attendre. */
    public function handleConflict(IcalConflictDetected $event): void
    {
        $this->notifications->dispatch(new IcalConflictNotification($event->block, $event->bookings));
    }

    /** L'hôte vérifie l'URL d'import ou synchronise à la main. */
    public function handleFailing(IcalFeedFailing $event): void
    {
        $this->notifications->dispatch(new IcalFeedFailingNotification($event->feed));
    }

    public function handleEmptyRefused(IcalEmptyCalendarRefused $event): void
    {
        $this->notifications->dispatch(new IcalEmptyCalendarRefusedNotification($event->feed));
    }

    public function handleRecovered(IcalFeedRecovered $event): void
    {
        $this->notifications->dispatch(new IcalFeedRecoveredNotification($event->feed));
    }
}
