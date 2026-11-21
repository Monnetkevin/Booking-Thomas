<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Mail\IcalConflictAlert;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BackOfficeNotification;
use App\Support\StayDates;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Collection;

/** Deux voyageurs attendus sur les mêmes nuits : e-mail toujours envoyé. */
class IcalConflictNotification extends BackOfficeNotification
{
    /**
     * @param  Collection<int, Booking>  $bookings
     */
    public function __construct(
        public readonly AvailabilityBlock $block,
        public readonly Collection $bookings,
    ) {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::IcalConflict;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Urgent;
    }

    public function title(): string
    {
        return __('notifications.ical_conflict.title', ['apartment' => $this->block->apartment->getTranslation('name', 'fr')], 'fr');
    }

    public function body(): string
    {
        return __('notifications.ical_conflict.body', [
            'provider' => $this->block->icalFeed?->provider->label() ?? 'la plateforme',
            'dates' => StayDates::range($this->block->starts_on, $this->block->ends_on, 'fr'),
            'count' => $this->bookings->count(),
        ], 'fr');
    }

    public function details(): array
    {
        return $this->bookings->map(fn (Booking $booking) => $booking->reference.' : '.$booking->guest_name.', '
            .StayDates::range($booking->arrival, $booking->departure, 'fr'))->values()->all();
    }

    public function link(): string
    {
        return '/admin/synchronisation';
    }

    public function dedupeKey(): string
    {
        return 'block:'.$this->block->id;
    }

    public function toMail(User $notifiable): Mailable
    {
        return (new IcalConflictAlert($this->block, $this->bookings))->to($notifiable->email, $notifiable->name);
    }
}
