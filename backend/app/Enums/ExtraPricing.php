<?php

namespace App\Enums;

use App\Services\Booking\Stay;

/**
 * Mode de tarification d'une option. Seule une option à l'unité se commande en plusieurs
 * exemplaires ; les autres se choisissent ou non, et leur prix suit le séjour.
 */
enum ExtraPricing: string
{
    case PerStay = 'per_stay';
    case PerNight = 'per_night';
    case PerGuest = 'per_guest';
    case PerGuestNight = 'per_guest_night';
    case PerUnit = 'per_unit';

    /** Nombre de fois que le prix unitaire s'applique au séjour, pour une quantité choisie. */
    public function units(Stay $stay, int $quantity): int
    {
        return match ($this) {
            self::PerStay => 1,
            self::PerNight => $stay->nights(),
            self::PerGuest => $stay->guests(),
            self::PerGuestNight => $stay->guests() * $stay->nights(),
            self::PerUnit => $quantity,
        };
    }

    public function hasQuantity(): bool
    {
        return $this === self::PerUnit;
    }
}
