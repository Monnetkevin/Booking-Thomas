<?php

namespace App\Mail;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Support\SiteUrl;
use App\Support\StayDates;
use Illuminate\Database\Eloquent\Collection;

/** Conflit de dates entre une plateforme et des réservations directes, pour l'hôte. */
class IcalConflictAlert extends TransactionalMail
{
    /**
     * @param  Collection<int, Booking>  $bookings
     */
    public function __construct(
        public readonly AvailabilityBlock $block,
        public readonly Collection $bookings,
    ) {
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.ical-conflict';
    }

    protected function subjectLine(): string
    {
        return __('mail.ical_conflict.subject', [
            'apartment' => $this->block->apartment->getTranslation('name', 'fr'),
            'dates' => StayDates::range($this->block->starts_on, $this->block->ends_on, 'fr'),
        ]);
    }

    protected function reason(): string
    {
        return __('mail.ical_conflict.reason');
    }

    protected function viewData(): array
    {
        return [
            'provider' => $this->block->icalFeed?->provider->label() ?? 'Airbnb',
            'apartment' => $this->block->apartment->getTranslation('name', 'fr'),
            'dates' => StayDates::range($this->block->starts_on, $this->block->ends_on, 'fr'),
            'externalUrl' => $this->block->external_url,
            'bookings' => $this->bookings->map(fn (Booking $booking) => [
                'reference' => $booking->reference,
                'guest' => $booking->guest_name,
                'dates' => StayDates::range($booking->arrival, $booking->departure, 'fr'),
            ])->all(),
            'adminUrl' => SiteUrl::to('/admin'),
        ];
    }
}
