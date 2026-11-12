<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\QuoteRequest;
use App\Http\Resources\Public\QuoteResource;
use App\Services\Booking\QuoteService;
use App\Services\Catalog\PublicCatalogService;

/** POST /api/quotes : détail du prix d'un séjour, recalculé par le serveur. */
class QuoteController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly QuoteService $quotes,
    ) {}

    public function store(QuoteRequest $request): QuoteResource
    {
        $apartment = $this->catalog->findPublished($request->string('apartment')->toString());

        return new QuoteResource($this->quotes->quote($apartment, $request->stay()));
    }
}
