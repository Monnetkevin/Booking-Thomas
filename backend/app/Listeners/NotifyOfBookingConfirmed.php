<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\Host\BookingConfirmedNotification;
use App\Services\Notifications\NotificationDispatcher;

/** Réservation directe confirmée : notification de l'hôte dans le back-office. */
class NotifyOfBookingConfirmed
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function handle(BookingConfirmed $event): void
    {
        $this->notifications->dispatch(new BookingConfirmedNotification($event->booking));
    }
}
