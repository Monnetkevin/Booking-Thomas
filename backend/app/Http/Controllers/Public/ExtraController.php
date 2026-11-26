<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ExtrasRequest;
use App\Http\Resources\Public\ExtraResource;
use App\Services\Catalog\PublicCatalogService;
use App\Services\Extra\ExtraSelector;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** GET /api/apartments/{slug}/extras : options encore commandables pour une arrivée. */
class ExtraController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly ExtraSelector $extras,
    ) {}

    public function index(ExtrasRequest $request, string $slug): AnonymousResourceCollection
    {
        return ExtraResource::collection($this->extras->available($this->catalog->findPublished($slug), $request->arrival()));
    }
}
