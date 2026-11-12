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

    public function findByReference(string $reference): ?Booking;

    /**
     * Réservations en attente dont le délai de paiement est dépassé.
     *
     * @return Collection<int, Booking>
     */
    public function overduePending(CarbonInterface $now): Collection;
}
