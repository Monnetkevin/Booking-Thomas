<?php

namespace App\Services\Statistics;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Collection;

/**
 * Revenu des réservations directes : argent encaissé, moins les remboursements, hors taxe de
 * séjour (reversée à la commune), compté au mois d'arrivée. Une réservation saisie à la main
 * non payée ne rapporte rien ; une annulation dont une partie est gardée rapporte cette part
 * (la taxe de séjour n'est pas due, le séjour n'a pas lieu). Même définition pour le tableau
 * de bord et les statistiques.
 */
class RevenueCalculator
{
    /**
     * @param  Collection<int, Booking>  $bookings  avec paid_amount et refunded_amount (earningArrivingBetween)
     */
    public function of(Collection $bookings): int
    {
        return (int) $bookings->sum(fn (Booking $booking) => $this->ofBooking($booking));
    }

    public function ofBooking(Booking $booking): int
    {
        $net = (int) $booking->getAttribute('paid_amount') - (int) $booking->getAttribute('refunded_amount');
        $tax = $booking->status === BookingStatus::Cancelled ? 0 : $booking->tourist_tax;

        return max(0, $net - $tax);
    }
}
