<?php

namespace App\Services\Booking;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Confirmation de réservation en PDF (pas une facture : décision du cadrage), dans la
 * langue du voyageur. Jointe aux e-mails et téléchargeable depuis la page de suivi.
 */
class BookingConfirmationPdf
{
    public function __construct(private readonly BookingSummary $summary) {}

    public function render(Booking $booking): string
    {
        return $this->build($booking, $booking->locale === 'en' ? 'en' : 'fr');
    }

    public function filename(Booking $booking): string
    {
        return "confirmation-{$booking->reference}.pdf";
    }

    private function build(Booking $booking, string $locale): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return Pdf::loadView('pdf.booking-confirmation', [
                'booking' => $booking,
                'summary' => $this->summary->for($booking, $locale),
                'issuedOn' => now()->locale($locale)->translatedFormat('j F Y'),
                'registration' => $booking->apartment->registration_number,
            ])
                ->setPaper('a4')
                // Seuls les caractères utilisés des polices sont inclus : quelques dizaines de Ko au lieu d'un Mo.
                ->setOption('isFontSubsettingEnabled', true)
                ->output();
        } finally {
            app()->setLocale($previous);
        }
    }
}
