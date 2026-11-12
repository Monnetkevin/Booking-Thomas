<?php

namespace App\Services\Booking;

use App\Mail\Support\MailLinks;
use App\Models\Booking;
use Illuminate\Support\Facades\URL;

/**
 * Liens personnels d'une réservation, reçus par e-mail. Signés (HMAC de la clé de
 * l'application) et sans date d'expiration : le voyageur s'en sert jusqu'à son séjour.
 * La signature porte sur le chemin relatif de l'API, donc valable quel que soit le
 * domaine (site public ou proxy de développement).
 */
class BookingLinks
{
    /** Page de suivi du site public : /reservation/{référence}?signature=… */
    public function trackingUrl(Booking $booking): string
    {
        return MailLinks::to("/reservation/{$booking->reference}", $booking->locale)
            .'?signature='.$this->signature('bookings.show', $booking);
    }

    /** Signature d'une route de l'API, transmise par la page du site. */
    public function signature(string $route, Booking $booking): string
    {
        $url = URL::signedRoute($route, ['booking' => $booking->reference], absolute: false);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return (string) ($query['signature'] ?? '');
    }
}
