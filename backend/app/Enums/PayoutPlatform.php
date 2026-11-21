<?php

namespace App\Enums;

/** Plateforme dont on importe les revenus (fichier CSV exporté par l'hôte). */
enum PayoutPlatform: string
{
    case Airbnb = 'airbnb';
    case Booking = 'booking';

    public function label(): string
    {
        return match ($this) {
            self::Airbnb => 'Airbnb',
            self::Booking => 'Booking.com',
        };
    }
}
