<?php

namespace App\Mail\Previews;

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Mail\NewContactMessage;
use App\Mail\TransactionalMail;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\ContactMessage;
use Closure;

/**
 * Modèles d'e-mails affichés par l'aperçu local (/dev/mails), avec des données d'exemple
 * jamais enregistrées. Un nouvel e-mail s'ajoute ici pour être relu avant envoi.
 */
class MailPreviewCatalog
{
    /**
     * @return array<string, array{label: string, build: Closure(string): TransactionalMail}>
     */
    public function all(): array
    {
        return [
            'contact' => [
                'label' => 'Nouveau message (hôte)',
                'build' => fn (string $locale): TransactionalMail => new NewContactMessage($this->sampleContact()),
            ],
            'booking-guest' => [
                'label' => 'Réservation confirmée (voyageur)',
                'build' => fn (string $locale): TransactionalMail => new BookingConfirmedGuest($this->sampleBooking($locale), 'hote@example.com'),
            ],
            'booking-host' => [
                'label' => 'Nouvelle réservation (hôte)',
                'build' => fn (string $locale): TransactionalMail => new BookingConfirmedHost($this->sampleBooking($locale)),
            ],
        ];
    }

    /**
     * @return (Closure(string): TransactionalMail)|null
     */
    public function find(string $name): ?Closure
    {
        return $this->all()[$name]['build'] ?? null;
    }

    /** Réservation confirmée d'exemple (jamais enregistrée), aussi utilisée pour l'aperçu du PDF. */
    public function sampleBooking(string $locale): Booking
    {
        $apartment = new Apartment([
            'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
            'address' => '12 rue de la Paix', 'postal_code' => '72000', 'city' => 'Le Mans',
            'check_in_time' => '16:00:00', 'check_out_time' => '10:00:00', 'registration_number' => '72181000123AB',
        ]);
        $booking = new Booking([
            'reference' => 'BT-EXEMPLE1', 'status' => BookingStatus::Confirmed, 'locale' => $locale,
            'guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com', 'guest_phone' => '+33 6 12 34 56 78',
            'guest_country' => 'FR', 'guest_message' => 'Nous arriverons vers 18 h. Un lit bébé serait possible ?',
            'adults' => 2, 'children' => 1, 'arrival' => '2026-12-18', 'departure' => '2026-12-21',
            'total_amount' => 31600,
            'price_breakdown' => ['lines' => [
                ['type' => 'accommodation', 'label' => $locale === 'en' ? '3 nights × €90' : '3 nuits × 90 €', 'amount' => 27000],
                ['type' => 'cleaning', 'label' => $locale === 'en' ? 'Cleaning fee' : 'Frais de ménage', 'amount' => 4000],
                ['type' => 'tourist_tax', 'label' => $locale === 'en' ? 'Tourist tax' : 'Taxe de séjour', 'amount' => 600],
            ]],
            'cancellation_policy' => ['policy' => 'moderate', 'tiers' => [
                ['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50],
            ]],
        ]);
        $booking->setRelation('apartment', $apartment);

        return $booking;
    }

    private function sampleContact(): ContactMessage
    {
        $contact = new ContactMessage([
            'name' => 'Camille Martin',
            'email' => 'camille@example.com',
            'phone' => '06 12 34 56 78',
            'message' => "Bonjour, le logement est-il disponible pour les 24 Heures du Mans ?\nNous serions deux adultes.",
        ]);
        $contact->setRelation('apartment', new Apartment(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]));

        return $contact;
    }
}
