<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\Extra;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use Carbon\CarbonInterface;
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

    public function all(): Collection
    {
        return Extra::query()->with(['apartments' => fn ($query) => $query->orderBy('position')])->orderBy('position')->orderBy('id')->get();
    }

    public function create(array $attributes, array $apartmentIds): Extra
    {
        $extra = Extra::query()->create($attributes);
        $extra->apartments()->sync($apartmentIds);

        return $extra->load('apartments');
    }

    public function update(Extra $extra, array $attributes, array $apartmentIds): Extra
    {
        $extra->update($attributes);
        $extra->apartments()->sync($apartmentIds);

        return $extra->load('apartments');
    }

    public function bookingsToPrepare(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereBetween('arrival', [$from->toDateString(), $to->toDateString()])
            ->whereHas('extras')
            ->with(['apartment', 'extras'])
            ->orderBy('arrival')
            ->orderBy('apartment_id')
            ->get();
    }

    public function monthFigures(CarbonInterface $from, CarbonInterface $to): array
    {
        $bookings = Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->where('arrival', '>=', $from->toDateString())
            ->where('arrival', '<', $to->toDateString());

        return [
            'bookings' => (clone $bookings)->count(),
            'with_extras' => (clone $bookings)->whereHas('extras')->count(),
            'revenue' => (int) BookingExtra::query()->whereIn('booking_id', (clone $bookings)->select('id'))->sum('amount'),
        ];
    }
}
