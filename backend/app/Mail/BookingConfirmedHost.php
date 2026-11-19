<?php

namespace App\Mail;

use App\Mail\Concerns\DescribesBooking;
use App\Models\Booking;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Address;

/** Nouvelle réservation confirmée, pour l'hôte (en français) ; « Répondre » écrit au voyageur. */
class BookingConfirmedHost extends TransactionalMail
{
    use DescribesBooking;

    public function __construct(public readonly Booking $booking)
    {
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.booking-confirmed-host';
    }

    protected function subjectLine(): string
    {
        return __('mail.booking.host_subject', [
            'apartment' => $this->booking->apartment->getTranslation('name', 'fr'),
            'dates' => $this->summary('fr')['dates'],
        ]);
    }

    protected function replyToAddress(): Address
    {
        return new Address($this->booking->guest_email, $this->booking->guest_name);
    }

    protected function reason(): string
    {
        return __('mail.booking.host_reason');
    }

    protected function viewData(): array
    {
        return [
            'summary' => $this->summary('fr'),
            'guest' => [
                'name' => $this->booking->guest_name,
                'email' => $this->booking->guest_email,
                'phone' => $this->booking->guest_phone,
                'country' => $this->booking->guest_country,
                'message' => $this->booking->guest_message,
            ],
            'adminUrl' => SiteUrl::to('/admin'),
        ];
    }
}
