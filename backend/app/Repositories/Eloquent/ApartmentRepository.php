<?php

namespace App\Repositories\Eloquent;

use App\Enums\ApartmentStatus;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ApartmentRepository implements ApartmentRepositoryInterface
{
    public function list(?ApartmentStatus $status = null): Collection
    {
        return Apartment::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['media', 'ratePeriods' => $this->upcomingPeriods()])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function loadDetails(Apartment $apartment): Apartment
    {
        return $apartment->load(['media', 'amenities', 'ratePeriods' => $this->upcomingPeriods()]);
    }

    public function create(array $attributes): Apartment
    {
        return Apartment::query()->create($attributes);
    }

    public function update(Apartment $apartment, array $attributes): Apartment
    {
        $apartment->update($attributes);

        return $apartment;
    }

    public function delete(Apartment $apartment): void
    {
        $apartment->delete();
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        // Les logements supprimés gardent leur slug (index unique) : ils comptent aussi.
        return Apartment::withTrashed()
            ->where('slug', $slug)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function nextPosition(): int
    {
        return (int) Apartment::query()->max('position') + 1;
    }

    public function hasUpcomingStays(Apartment $apartment, CarbonInterface $today): bool
    {
        $holding = array_filter(BookingStatus::cases(), fn (BookingStatus $status) => $status->holdsNights());

        return $apartment->bookings()
            ->whereIn('status', $holding)
            ->where('departure', '>', $today->toDateString())
            ->exists();
    }

    public function hasUpcomingRatePeriod(Apartment $apartment, CarbonInterface $today): bool
    {
        return $apartment->ratePeriods()->where('ends_on', '>=', $today->toDateString())->exists();
    }

    public function photoCount(Apartment $apartment): int
    {
        return $apartment->media()->where('collection_name', Apartment::PHOTOS)->count();
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                Apartment::query()->whereKey($id)->update(['position' => $position]);
            }
        });
    }

    public function syncAmenities(Apartment $apartment, array $amenityIds): void
    {
        $apartment->amenities()->sync($amenityIds);
    }

    /**
     * Périodes qui ne sont pas terminées, la plus proche d'abord (prix « de base » affiché).
     *
     * @return \Closure(HasMany<RatePeriod, Apartment>): void
     */
    private function upcomingPeriods(): \Closure
    {
        return function (HasMany $query): void {
            $query->where('ends_on', '>=', now()->toDateString())->reorder('starts_on');
        };
    }
}
