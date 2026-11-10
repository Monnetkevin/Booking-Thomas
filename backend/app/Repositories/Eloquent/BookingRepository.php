<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;

class BookingRepository implements BookingRepositoryInterface
{
    public function referenceExists(string $reference): bool
    {
        return Booking::query()->where('reference', $reference)->exists();
    }
}
