<?php

namespace App\Repositories\Contracts;

use App\Models\Apartment;
use App\Models\RatePeriod;
use Illuminate\Database\Eloquent\Collection;

interface RatePeriodRepositoryInterface
{
    /**
     * Périodes du logement, de la plus ancienne à la plus lointaine.
     *
     * @return Collection<int, RatePeriod>
     */
    public function forApartment(Apartment $apartment, bool $includePast = false): Collection;

    /** Première période du logement qui recouvre [from, to] (dates incluses), hors $exceptId. */
    public function findOverlapping(int $apartmentId, string $from, string $to, ?int $exceptId = null): ?RatePeriod;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Apartment $apartment, array $attributes): RatePeriod;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(RatePeriod $period, array $attributes): RatePeriod;

    public function delete(RatePeriod $period): void;
}
