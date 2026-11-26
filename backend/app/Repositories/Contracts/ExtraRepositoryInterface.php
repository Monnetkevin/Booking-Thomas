<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Extra;
use Illuminate\Database\Eloquent\Collection;

interface ExtraRepositoryInterface
{
    /**
     * Options actives proposées dans ce logement, dans l'ordre du catalogue.
     *
     * @return Collection<int, Extra>
     */
    public function activeForApartment(int $apartmentId): Collection;

    /**
     * Options figées dans la réservation.
     *
     * @param  list<array{extra_id: int, name: string, pricing: string, quantity: int, unit_amount: int, amount: int}>  $items
     */
    public function recordForBooking(Booking $booking, array $items): void;
}
