<?php

namespace App\Repositories\Eloquent;

use App\Models\Amenity;
use App\Repositories\Contracts\AmenityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AmenityRepository implements AmenityRepositoryInterface
{
    public function all(): Collection
    {
        return Amenity::query()->orderBy('id')->get();
    }
}
