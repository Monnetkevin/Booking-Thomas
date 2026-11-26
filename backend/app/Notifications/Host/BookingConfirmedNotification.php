<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\Booking;
use App\Notifications\BackOfficeNotification;
use App\Support\Money;
use App\Support\StayDates;

/** Réservation directe confirmée ; la copie complète (PDF) part aux adresses du réglage. */
class BookingConfirmedNotification extends BackOfficeNotification
{
    public function __construct(public readonly Booking $booking)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::BookingConfirmed;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.booking_confirmed.title', ['apartment' => $this->booking->apartment->getTranslation('name', 'fr')], 'fr');
    }

    public function body(): string
    {
        return __('notifications.booking_confirmed.body', [
            'guest' => $this->booking->guest_name,
            'dates' => StayDates::range($this->booking->arrival, $this->booking->departure, 'fr'),
            'guests' => $this->booking->adults + $this->booking->children,
            'total' => Money::format($this->booking->total_amount),
        ], 'fr');
    }

    public function link(): string
    {
        return '/admin';
    }

    public function dedupeKey(): string
    {
        return 'booking:'.$this->booking->id;
    }
}
