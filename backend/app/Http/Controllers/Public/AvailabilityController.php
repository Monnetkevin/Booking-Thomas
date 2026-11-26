<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CalendarRequest;
use App\Http\Requests\Public\SearchRequest;
use App\Http\Resources\Public\QuoteResource;
use App\Services\Availability\AvailabilityService;
use App\Services\Catalog\CatalogPresenter;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalog,
        private readonly AvailabilityService $availability,
        private readonly CatalogPresenter $presenter,
    ) {}

    /** GET /api/apartments/{slug}/availability : calendrier d'un logement. */
    public function calendar(CalendarRequest $request, string $slug): JsonResponse
    {
        $apartment = $this->catalog->findPublished($slug);

        return response()->json(['data' => $this->availability->calendar(
            $apartment,
            $request->input('from'),
            $request->input('to'),
        )]);
    }

    /** GET /api/availability : logements libres pour un séjour, avec leur prix. */
    public function search(SearchRequest $request): JsonResponse
    {
        $results = $this->availability->search($this->catalog->published(), $request->stay());

        return response()->json(['data' => $results->map(fn (array $result) => [
            ...$this->presenter->summary($result['apartment'], app()->getLocale()),
            'available' => $result['available'],
            'quote' => $result['quote'] ? (new QuoteResource($result['quote']))->resolve($request) : null,
            'unavailable_reason' => $result['reason'],
        ])->values()]);
    }
}
