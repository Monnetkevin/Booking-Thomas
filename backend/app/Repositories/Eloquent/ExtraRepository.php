<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\Extra;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExtraRepository implements ExtraRepositoryInterface
{
    public function activeForApartment(int $apartmentId): Collection
    {
        return Extra::query()
            ->where('is_active', true)
            ->whereHas('apartments', fn (Builder $query) => $query->whereKey($apartmentId))
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function recordForBooking(Booking $booking, array $items): void
    {
        if ($items !== []) {
            $booking->extras()->createMany($items);
        }
    }
}
