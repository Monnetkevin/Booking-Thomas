<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Photo\ReorderPhotosRequest;
use App\Http\Requests\Admin\Photo\UpdatePhotoRequest;
use App\Http\Requests\Admin\Photo\UploadPhotosRequest;
use App\Http\Resources\Admin\PhotoResource;
use App\Models\Apartment;
use App\Services\Apartment\ApartmentPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ApartmentPhotoController extends Controller
{
    public function __construct(private readonly ApartmentPhotoService $photos) {}

    public function index(Apartment $apartment): AnonymousResourceCollection
    {
        Gate::authorize('view', $apartment);

        return PhotoResource::collection($this->photos->list($apartment));
    }

    public function store(UploadPhotosRequest $request, Apartment $apartment): JsonResponse
    {
        Gate::authorize('update', $apartment);

        $uploaded = $this->photos->upload($apartment, $request->photos());

        return PhotoResource::collection($uploaded)->response()->setStatusCode(201);
    }

    public function reorder(ReorderPhotosRequest $request, Apartment $apartment): AnonymousResourceCollection
    {
        Gate::authorize('update', $apartment);

        return PhotoResource::collection($this->photos->reorder($apartment, $request->orderedIds()));
    }

    public function update(UpdatePhotoRequest $request, Apartment $apartment, int $photo): PhotoResource
    {
        Gate::authorize('update', $apartment);

        /** @var array{fr?: string|null, en?: string|null} $alt */
        $alt = $request->validated('alt');

        return new PhotoResource($this->photos->updateAlt($apartment, $photo, $alt));
    }

    public function destroy(Apartment $apartment, int $photo): Response
    {
        Gate::authorize('update', $apartment);

        $this->photos->delete($apartment, $photo);

        return response()->noContent();
    }
}
