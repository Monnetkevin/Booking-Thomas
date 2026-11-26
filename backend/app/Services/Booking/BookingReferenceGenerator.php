<?php

namespace App\Services\Booking;

use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Référence publique d'une réservation, courte et non devinable : « BT-7K2M9QXA ».
 * Elle apparaît dans les e-mails, le lien de suivi et la session Stripe.
 */
class BookingReferenceGenerator
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    public function generate(): string
    {
        do {
            $reference = 'BT-'.Str::upper(Str::random(8));
        } while ($this->bookings->referenceExists($reference));

        return $reference;
    }
}
