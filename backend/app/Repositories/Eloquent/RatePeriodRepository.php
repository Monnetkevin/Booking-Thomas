<?php

namespace App\Repositories\Eloquent;

use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RatePeriodRepository implements RatePeriodRepositoryInterface
{
    public function forApartment(Apartment $apartment, bool $includePast = false): Collection
    {
        return RatePeriod::query()
            ->where('apartment_id', $apartment->id)
            ->unless($includePast, fn (Builder $query) => $query->where('ends_on', '>=', now()->toDateString()))
            ->orderBy('starts_on')
            ->get();
    }

    public function findOverlapping(int $apartmentId, string $from, string $to, ?int $exceptId = null): ?RatePeriod
    {
        return RatePeriod::query()
            ->where('apartment_id', $apartmentId)
            ->where('starts_on', '<=', $to)
            ->where('ends_on', '>=', $from)
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->orderBy('starts_on')
            ->first();
    }

    public function create(Apartment $apartment, array $attributes): RatePeriod
    {
        return $apartment->ratePeriods()->create($attributes);
    }

    public function update(RatePeriod $period, array $attributes): RatePeriod
    {
        $period->update($attributes);

        return $period;
    }

    public function delete(RatePeriod $period): void
    {
        $period->delete();
    }
}
