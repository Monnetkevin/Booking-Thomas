<?php

namespace App\Repositories\Contracts;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

interface AvailabilityRepositoryInterface
{
    /** Un bloc actif (hors conflit signalé) touche-t-il une nuit de [from, to[ ? */
    public function hasActiveBlock(int $apartmentId, string $from, string $to): bool;

    /**
     * Blocs actifs qui touchent [from, to[, pour le calendrier.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function activeBlocks(int $apartmentId, string $from, string $to): Collection;

    /** Bloque les nuits [arrivée, départ[ d'une réservation (contrainte d'exclusion). */
    public function blockForBooking(Booking $booking): AvailabilityBlock;

    /** Libère les nuits d'une réservation (expirée ou annulée). */
    public function releaseBooking(Booking $booking): void;
}
