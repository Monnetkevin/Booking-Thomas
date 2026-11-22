<?php

namespace App\Enums;

/**
 * Types de notification du back-office : à qui ils s'adressent et comment ils peuvent
 * partir par e-mail. Un nouveau type = un nouveau cas et sa classe de notification.
 */
enum NotificationType: string
{
    case IcalConflict = 'ical_conflict';
    case IcalFeedFailing = 'ical_feed_failing';
    case IcalEmptyCalendarRefused = 'ical_empty_calendar_refused';
    case IcalFeedRecovered = 'ical_feed_recovered';
    case BookingConfirmed = 'booking_confirmed';
    case ContactMessage = 'contact_message';
    case StayChanged = 'stay_changed';
    case DailySummary = 'daily_summary';

    public function audience(): UserRole
    {
        return match ($this) {
            self::StayChanged, self::DailySummary => UserRole::Housekeeping,
            default => UserRole::Admin,
        };
    }

    /**
     * Peut partir par e-mail. Les réservations confirmées et les messages de contact sont
     * déjà envoyés, avec leur contenu complet, aux adresses du réglage « e-mails de notification ».
     */
    public function emailable(): bool
    {
        return ! in_array($this, [self::BookingConfirmed, self::ContactMessage], true);
    }

    /** E-mail toujours envoyé : l'hôte ne peut pas le couper (deux voyageurs sur les mêmes nuits). */
    public function emailForced(): bool
    {
        return $this === self::IcalConflict;
    }

    /** E-mail activé par défaut, avant tout réglage du compte. */
    public function emailByDefault(): bool
    {
        return match ($this) {
            self::IcalFeedRecovered => false,
            default => $this->emailable(),
        };
    }

    /**
     * Types qu'un rôle peut recevoir, dans l'ordre d'affichage des préférences.
     *
     * @return list<self>
     */
    public static function for(UserRole $role): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->audience() === $role));
    }
}
