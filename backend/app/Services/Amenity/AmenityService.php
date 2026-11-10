<?php

namespace App\Services\Amenity;

use App\Models\Amenity;
use App\Repositories\Contracts\AmenityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/** Catalogue des équipements que l'hôte coche pour chaque logement. */
class AmenityService
{
    public function __construct(private readonly AmenityRepositoryInterface $amenities) {}

    /**
     * @return Collection<int, Amenity>
     */
    public function catalog(): Collection
    {
        return $this->amenities->all();
    }
}
