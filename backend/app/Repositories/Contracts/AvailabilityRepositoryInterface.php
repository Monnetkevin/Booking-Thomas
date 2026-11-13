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

    /**
     * Verrou par logement, tenu jusqu'à la fin de la transaction en cours : deux créations
     * de réservation sur le même logement passent l'une après l'autre.
     */
    public function lockApartment(int $apartmentId): void;

    /** Bloque les nuits [arrivée, départ[ d'une réservation (contrainte d'exclusion). */
    public function blockForBooking(Booking $booking): AvailabilityBlock;

    /** Libère les nuits d'une réservation (expirée ou annulée). */
    public function releaseBooking(Booking $booking): void;

    /**
     * Blocs actifs hors conflit qui touchent [from, to[, avec leur réservation éventuelle.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function overlappingActive(int $apartmentId, string $from, string $to, ?int $exceptBlockId = null): Collection;

    /**
     * Nuits occupées à publier pour une plateforme : blocs actifs du logement qui ne sont
     * pas terminés au jour $from, sauf ceux importés de son propre calendrier (pas de
     * boucle : la plateforme les connaît déjà).
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function exportable(int $apartmentId, int $exceptFeedId, string $from): Collection;

    /**
     * Tous les blocs importés d'un flux iCal, actifs ou non.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function feedBlocks(int $feedId): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createBlock(array $attributes): AvailabilityBlock;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateBlock(AvailabilityBlock $block, array $attributes): AvailabilityBlock;
}
