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
     * Vrai si l'événement est une date fermée par l'hôte et non un séjour. Airbnb les
     * distingue (« Reserved » / « Airbnb (Not available) ») ; Booking.com marque tout
     * « CLOSED - Not available » : voir confirmsStaysByPayout().
     */
    public function isClosure(?string $summary): bool
    {
        return match ($this) {
            self::Airbnb => str_contains(mb_strtolower((string) $summary), 'not available'),
            self::Booking => false,
        };
    }

    /**
     * Vrai si le calendrier ne distingue pas un séjour d'une date fermée : un bloc n'y compte
     * comme séjour vendu, dans les chiffres du back-office, que si un versement importé le confirme.
     */
    public function confirmsStaysByPayout(): bool
    {
        return $this === self::Booking;
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
