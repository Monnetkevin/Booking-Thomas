<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicApartmentDetailResource;
use App\Http\Resources\Public\PublicApartmentResource;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lecture publique des logements publiés (aucune authentification, réponses en cache).
 */
class ApartmentController extends Controller
{
    public function __construct(private readonly PublicCatalogService $catalog) {}

    public function index(): AnonymousResourceCollection
    {
        return PublicApartmentResource::collection($this->catalog->published());
    }

    public function show(string $slug): PublicApartmentDetailResource
    {
        $apartment = $this->catalog->findPublished($slug);

        return new PublicApartmentDetailResource($apartment, $this->catalog->cancellationTiers($apartment->cancellation_policy));
    }
}
