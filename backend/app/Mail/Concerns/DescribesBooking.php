<?php

namespace App\Mail\Concerns;

use App\Models\Booking;
use App\Services\Booking\BookingConfirmationPdf;
use App\Services\Booking\BookingSummary;
use Illuminate\Mail\Mailables\Attachment;

/**
 * E-mails qui décrivent une réservation : récapitulatif commun (e-mails et PDF) et
 * confirmation PDF jointe.
 *
 * @property-read Booking $booking
 */
trait DescribesBooking
{
    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(BookingConfirmationPdf::class);

        return [Attachment::fromData(fn () => $pdf->render($this->booking), $pdf->filename($this->booking))->withMime('application/pdf')];
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(string $locale): array
    {
        return app(BookingSummary::class)->for($this->booking, $locale);
    }
}
