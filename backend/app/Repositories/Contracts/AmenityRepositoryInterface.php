<?php

namespace App\Repositories\Contracts;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Collection;

interface AmenityRepositoryInterface
{
    /**
     * @return Collection<int, Amenity>
     */
    public function all(): Collection;
}
