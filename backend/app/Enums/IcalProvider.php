<?php

namespace App\Enums;

/**
 * Plateforme dont on importe le calendrier. Ajouter une plateforme se limite à un nouveau
 * cas : le modèle de données est générique. Les valeurs sont celles de PayoutPlatform, pour
 * rapprocher les nuits importées des revenus importés.
 */
enum IcalProvider: string
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

    /**
     * L'URL d'import doit appartenir à la plateforme : le serveur ne télécharge jamais une
     * adresse quelconque (réseau interne, autre site).
     */
    public function allowsHost(string $host): bool
    {
        return match ($this) {
            self::Airbnb => (bool) preg_match('/(^|\.)airbnb\.[a-z]{2,3}(\.[a-z]{2})?$/i', $host),
            // Extranet : admin.booking.com, ical.booking.com…
            self::Booking => (bool) preg_match('/(^|\.)booking\.com$/i', $host),
        };
    }
}
