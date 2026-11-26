<?php

namespace App\Mail;

use App\Mail\Concerns\DescribesBooking;
use App\Models\Booking;
use Illuminate\Mail\Mailables\Address;

/** Confirmation au voyageur, dans sa langue, avec le lien de suivi et le PDF. */
class BookingConfirmedGuest extends TransactionalMail
{
    use DescribesBooking;

    public function __construct(public readonly Booking $booking, public readonly ?string $hostEmail = null)
    {
        $this->prepare($booking->locale);
    }

    protected function markdownView(): string
    {
        return 'mail.booking-confirmed-guest';
    }

    protected function subjectLine(): string
    {
        return __('mail.booking.guest_subject', ['apartment' => $this->summary((string) $this->locale)['apartment'], 'reference' => $this->booking->reference]);
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
        return ['summary' => $this->summary((string) $this->locale), 'guestName' => $this->booking->guest_name];
    }
}
