<?php

namespace App\Repositories\Contracts;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

interface AvailabilityRepositoryInterface
{
    /**
     * Un bloc actif touche-t-il une nuit de [from, to[ ? Les blocs en conflit comptent :
     * leurs nuits sont occupées, même celles qu'ils sont seuls à tenir.
     */
    public function hasActiveBlock(int $apartmentId, string $from, string $to): bool;

    /**
     * Blocs actifs (en conflit ou non) qui touchent [from, to[, pour le calendrier.
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

    /** Bloc actif des nuits d'une réservation, ou null. */
    public function bookingBlock(Booking $booking): ?AvailabilityBlock;

    /**
     * Blocs actifs hors conflit qui touchent [from, to[, avec leur réservation éventuelle :
     * ceux contre lesquels un nouveau bloc serait en conflit (contrainte d'exclusion).
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function overlappingActive(int $apartmentId, string $from, string $to, ?int $exceptBlockId = null): Collection;

    /**
     * Blocs actifs en conflit qui touchent [from, to[, du plus ancien au plus récent.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function conflictsOverlapping(int $apartmentId, string $from, string $to): Collection;

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

    /** Supprime le bloc ; renvoie false s'il n'existait déjà plus. */
    public function deleteBlock(AvailabilityBlock $block): bool;

    /**
     * Un autre séjour (réservation ou plateforme) actif du logement part le jour d'arrivée
     * ou arrive le jour de départ : ménage entre deux séjours le même jour.
     */
    public function hasSameDayTurnover(int $apartmentId, string $startsOn, string $endsOn, int $exceptBlockId): bool;

    /**
     * Blocs actifs qui commencent ou finissent ce jour-là, avec logement et réservation,
     * pour le récapitulatif du ménage : logements existants, réservations confirmées
     * (pas celles en attente de paiement).
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function movementsOn(string $date): Collection;

    /**
     * Blocs actifs de tous les logements qui touchent [from, to[, avec réservation et
     * calendrier, pour le planning.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function planningBlocks(string $from, string $to): Collection;

    /**
     * Séjours importés en conflit qui ne sont pas terminés, avec leur logement.
     *
     * @return Collection<int, AvailabilityBlock>
     */
    public function upcomingConflicts(string $today): Collection;
}
