<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Search\SearchRequest;
use App\Http\Resources\Admin\SearchResultsResource;
use App\Models\Booking;
use App\Services\Search\AdminSearchService;
use Illuminate\Support\Facades\Gate;

/** Recherche rapide du back-office (Ctrl K). */
class SearchController extends Controller
{
    public function __construct(private readonly AdminSearchService $search) {}

    public function __invoke(SearchRequest $request): SearchResultsResource
    {
        Gate::authorize('viewAny', Booking::class);

        return new SearchResultsResource($this->search->search($request->term()));
    }
}
