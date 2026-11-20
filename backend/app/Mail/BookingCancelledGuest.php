<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\StayDates;
use Illuminate\Mail\Mailables\Address;

/** Annulation d'une réservation par l'hôte, prévenue au voyageur dans sa langue. */
class BookingCancelledGuest extends TransactionalMail
{
    public function __construct(public readonly Booking $booking, public readonly ?string $hostEmail = null)
    {
        $this->prepare($booking->locale);
    }

    protected function markdownView(): string
    {
        return 'mail.booking-cancelled-guest';
    }

    protected function subjectLine(): string
    {
        return __('mail.booking_cancelled.subject', ['reference' => $this->booking->reference]);
    }

    protected function replyToAddress(): ?Address
    {
        return $this->hostEmail ? new Address($this->hostEmail, (string) config('app.name')) : null;
    }

    protected function reason(): string
    {
        return __('mail.booking.reason', ['reference' => $this->booking->reference]);
    }

    protected function viewData(): array
    {
        $locale = (string) $this->locale;

        return [
            'guestName' => $this->booking->guest_name,
            'reference' => $this->booking->reference,
            'apartment' => $this->booking->apartment->getTranslation('name', $locale),
            'dates' => StayDates::range($this->booking->arrival, $this->booking->departure, $locale),
        ];
    }
}
