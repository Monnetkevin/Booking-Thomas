<?php

namespace App\Services\Statistics;

use App\Models\Booking;
use Illuminate\Support\Collection;

/**
 * Revenu de réservations vendues : total payé hors taxe de séjour (reversée à la commune),
 * compté au mois d'arrivée. Même définition pour le tableau de bord et les statistiques.
 */
class RevenueCalculator
{
    /**
     * @param  Collection<int, Booking>  $bookings
     */
    public function of(Collection $bookings): int
    {
        return (int) $bookings->sum(fn (Booking $booking) => $booking->total_amount - $booking->tourist_tax);
    }
}
