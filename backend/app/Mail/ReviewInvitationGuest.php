<?php

namespace App\Mail;

use App\Mail\Support\MailLinks;
use App\Models\Booking;
use App\Support\StayDates;
use Carbon\CarbonImmutable;

/**
 * Invitation à laisser un avis, le lendemain du départ, dans la langue du voyageur.
 * Le lien porte un jeton personnel à usage unique (seule son empreinte est enregistrée).
 */
class ReviewInvitationGuest extends TransactionalMail
{
    public function __construct(
        public readonly Booking $booking,
        public readonly string $token,
        public readonly CarbonImmutable $expiresAt,
    ) {
        $this->prepare($booking->locale);
    }

    protected function markdownView(): string
    {
        return 'mail.review-invitation';
    }

    protected function subjectLine(): string
    {
        return __('mail.review_invitation.subject', ['apartment' => $this->apartmentName()]);
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
            'apartment' => $this->apartmentName(),
            'dates' => StayDates::range($this->booking->arrival, $this->booking->departure, $locale),
            'url' => MailLinks::to("/avis/{$this->token}", $locale),
            'expiresOn' => $this->expiresAt->locale($locale)->isoFormat('LL'),
        ];
    }

    private function apartmentName(): string
    {
        return $this->booking->apartment->getTranslation('name', (string) $this->locale);
    }
}
