<?php

namespace App\Enums;

/** État du règlement d'une réservation, pour la liste du back-office. */
enum BookingPaymentState: string
{
    /** Paiement en cours (réservation en attente). */
    case Awaiting = 'awaiting';
    /** Rien reçu (réservation saisie à la main non réglée, ou expirée). */
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public static function of(BookingStatus $status, int $paid, int $refunded): self
    {
        return match (true) {
            $status === BookingStatus::Pending => self::Awaiting,
            $paid <= 0 => self::Unpaid,
            $refunded >= $paid => self::Refunded,
            $refunded > 0 => self::PartiallyRefunded,
            default => self::Paid,
        };
    }
}
