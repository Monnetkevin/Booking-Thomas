<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Carbon\CarbonInterface;
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
}
