<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Services\Booking\BookingFilters;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    public function referenceExists(string $reference): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Booking;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Booking $booking, array $attributes): Booking;

    /**
     * Fait quitter le statut « pending » à une réservation, seulement si elle y est encore
     * en base (mise à jour conditionnelle atomique). Renvoie false si une autre opération
     * (confirmation, expiration) est passée avant : le modèle est alors rechargé.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function transitionFromPending(Booking $booking, array $attributes): bool;

    public function findByReference(string $reference): ?Booking;

    /**
     * Réservations en attente dont le délai de paiement est dépassé.
     *
     * @return Collection<int, Booking>
     */
    public function overduePending(CarbonInterface $now): Collection;

    /**
     * Réservations du back-office, arrivées les plus proches d'abord, avec leur logement.
     *
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginateForAdmin(BookingFilters $filters, int $perPage): LengthAwarePaginator;

    /**
     * Même liste sans pagination (export), limitée.
     *
     * @return Collection<int, Booking>
     */
    public function allForAdmin(BookingFilters $filters, int $limit): Collection;

    /**
     * Nombre de réservations par onglet (toutes, à venir, en cours, terminées, annulées),
     * avec les autres filtres.
     *
     * @return array<string, int>
     */
    public function countByStage(BookingFilters $filters): array;

    /** Charge ce qu'affiche la fiche d'une réservation (logement, paiements). */
    public function loadDetails(Booking $booking): Booking;

    /**
     * Recherche rapide (référence, nom ou e-mail du voyageur), séjours les plus récents d'abord.
     *
     * @return Collection<int, Booking>
     */
    public function search(string $term, int $limit): Collection;

    /**
     * Fait passer une réservation de confirmée à annulée, seulement si elle l'est encore en
     * base (mise à jour conditionnelle) ; false si une autre opération est passée avant.
     */
    public function cancelIfConfirmed(Booking $booking): bool;

    /** Règlement reçu hors ligne (espèces, virement) : paiement réussi, sans Stripe. */
    public function recordOfflinePayment(Booking $booking, int $amount): void;

    /** Remboursement versé hors ligne (virement) en attendant Stripe : remboursement réussi. */
    public function recordOfflineRefund(Booking $booking, int $amount): void;

    /**
     * Réservations qui peuvent rapporter (confirmées, terminées, ou annulées avec une somme
     * gardée) qui arrivent dans [from, to[, avec paid_amount et refunded_amount.
     *
     * @return Collection<int, Booking>
     */
    public function earningArrivingBetween(CarbonInterface $from, CarbonInterface $to): Collection;

    /**
     * Passe en « terminées » les réservations confirmées dont le départ est passé (avant ce jour).
     *
     * @return int nombre de réservations terminées
     */
    public function completeDepartedBefore(CarbonInterface $day): int;

    /**
     * Séjours terminés à inviter à laisser un avis : réservations du site (en ligne ou saisies à
     * la main) avec un e-mail, parties entre ces deux jours inclus, sans invitation envoyée.
     *
     * @return Collection<int, Booking>
     */
    public function awaitingReviewInvitation(CarbonInterface $from, CarbonInterface $to): Collection;
}
