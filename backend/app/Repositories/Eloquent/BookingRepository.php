<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function referenceExists(string $reference): bool
    {
        return Booking::query()->where('reference', $reference)->exists();
    }

    public function create(array $attributes): Booking
    {
        return Booking::query()->create($attributes);
    }

    public function update(Booking $booking, array $attributes): Booking
    {
        $booking->update($attributes);

        return $booking;
    }

    public function transitionFromPending(Booking $booking, array $attributes): bool
    {
        $changed = Booking::query()
            ->whereKey($booking->getKey())
            ->where('status', BookingStatus::Pending)
            ->update($attributes) === 1;

        $booking->refresh();

        return $changed;
    }

    public function findByReference(string $reference): ?Booking
    {
        return Booking::query()->where('reference', $reference)->first();
    }

    public function overduePending(CarbonInterface $now): Collection
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where('expires_at', '<', $now)
            ->get();
    }
}
