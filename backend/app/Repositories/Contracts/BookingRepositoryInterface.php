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

    /** Encaissé sur la période : paiements réussis moins remboursements réussis, en centimes. */
    public function netPaymentsBetween(CarbonInterface $from, CarbonInterface $to): int;

    /**
     * Réservations vendues (confirmées ou terminées) qui arrivent dans [from, to[.
     *
     * @return Collection<int, Booking>
     */
    public function soldArrivingBetween(CarbonInterface $from, CarbonInterface $to): Collection;
}
