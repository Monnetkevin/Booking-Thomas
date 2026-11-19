<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\Host\BookingConfirmedNotification;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Housekeeping\HousekeepingNotifier;
use App\Services\Housekeeping\StayChange;
use App\Services\Notifications\NotificationDispatcher;

/** Réservation directe confirmée : notification de l'hôte, nouveau séjour pour le ménage. */
class NotifyOfBookingConfirmed
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly HousekeepingNotifier $housekeeping,
        private readonly AvailabilityRepositoryInterface $availability,
    ) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $this->notifications->dispatch(new BookingConfirmedNotification($booking));

        $block = $this->availability->bookingBlock($booking);
        if ($block !== null) {
            $this->housekeeping->stayChanged(StayChange::added($block, $booking->adults + $booking->children));
        }
    }
}
