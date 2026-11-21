<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

/**
 * Réservation saisie à la main dont le total vaut 0 € : un séjour sans montant (logement
 * prêté, usage personnel) se saisit comme une fermeture de dates, hors statistiques.
 */
class FreeStayException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('booking.errors.free_stay', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'free_stay';
    }
}
