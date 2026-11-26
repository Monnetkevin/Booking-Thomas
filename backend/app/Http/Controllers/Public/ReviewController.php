<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ListReviewsRequest;
use App\Services\Review\PublicReviewService;
use Illuminate\Http\JsonResponse;

/** Avis publiés (lecture publique, en cache) avec la moyenne et la répartition des notes. */
class ReviewController extends Controller
{
    public function __construct(private readonly PublicReviewService $reviews) {}

    public function index(ListReviewsRequest $request): JsonResponse
    {
        return response()->json($this->reviews->page($request->apartment(), $request->page(), app()->getLocale()));
    }
}
