<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Apartment\SyncAmenitiesRequest;
use App\Http\Resources\Admin\AdminApartmentResource;
use App\Http\Resources\Admin\AmenityResource;
use App\Models\Amenity;
use App\Models\Apartment;
use App\Services\Amenity\AmenityService;
use App\Services\Apartment\ApartmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AmenityController extends Controller
{
    public function __construct(
        private readonly AmenityService $amenities,
        private readonly ApartmentService $apartments,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Amenity::class);

        return AmenityResource::collection($this->amenities->catalog());
    }

    public function sync(SyncAmenitiesRequest $request, Apartment $apartment): AdminApartmentResource
    {
        Gate::authorize('update', $apartment);

        return new AdminApartmentResource($this->apartments->syncAmenities($apartment, $request->amenityIds()));
    }
}
