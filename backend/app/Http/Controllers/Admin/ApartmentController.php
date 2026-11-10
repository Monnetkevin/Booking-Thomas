<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Apartment\IndexApartmentsRequest;
use App\Http\Requests\Admin\Apartment\ReorderApartmentsRequest;
use App\Http\Requests\Admin\Apartment\StoreApartmentRequest;
use App\Http\Requests\Admin\Apartment\UpdateApartmentRequest;
use App\Http\Resources\Admin\AdminApartmentListResource;
use App\Http\Resources\Admin\AdminApartmentResource;
use App\Models\Apartment;
use App\Services\Apartment\ApartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ApartmentController extends Controller
{
    public function __construct(private readonly ApartmentService $apartments) {}

    public function index(IndexApartmentsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Apartment::class);

        return AdminApartmentListResource::collection($this->apartments->list($request->status()));
    }

    public function store(StoreApartmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Apartment::class);

        $apartment = $this->apartments->create($request->apartmentData());

        return (new AdminApartmentResource($apartment))->response()->setStatusCode(201);
    }

    public function show(Apartment $apartment): AdminApartmentResource
    {
        Gate::authorize('view', $apartment);

        return new AdminApartmentResource($this->apartments->details($apartment));
    }

    public function update(UpdateApartmentRequest $request, Apartment $apartment): AdminApartmentResource
    {
        Gate::authorize('update', $apartment);

        return new AdminApartmentResource($this->apartments->update($apartment, $request->apartmentData()));
    }

    public function publish(Apartment $apartment): AdminApartmentResource
    {
        Gate::authorize('publish', $apartment);

        return new AdminApartmentResource($this->apartments->publish($apartment));
    }

    public function unpublish(Apartment $apartment): AdminApartmentResource
    {
        Gate::authorize('publish', $apartment);

        return new AdminApartmentResource($this->apartments->unpublish($apartment));
    }

    public function reorder(ReorderApartmentsRequest $request): Response
    {
        Gate::authorize('reorder', Apartment::class);

        $this->apartments->reorder($request->orderedIds());

        return response()->noContent();
    }

    public function destroy(Apartment $apartment): Response
    {
        Gate::authorize('delete', $apartment);

        $this->apartments->delete($apartment);

        return response()->noContent();
    }
}
