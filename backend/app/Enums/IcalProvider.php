<?php

namespace App\Enums;

/**
 * Plateforme dont on importe le calendrier. Ajouter une plateforme (Booking.com…) se
 * limite à un nouveau cas : le modèle de données est générique.
 */
enum IcalProvider: string
{
    case Airbnb = 'airbnb';

    public function label(): string
    {
        return match ($this) {
            self::Airbnb => 'Airbnb',
        };
    }

    /**
     * L'URL d'import doit appartenir à la plateforme : le serveur ne télécharge jamais une
     * adresse quelconque (réseau interne, autre site).
     */
    public function allowsHost(string $host): bool
    {
        return match ($this) {
            self::Airbnb => (bool) preg_match('/(^|\.)airbnb\.[a-z]{2,3}(\.[a-z]{2})?$/i', $host),
        };
    }
}
