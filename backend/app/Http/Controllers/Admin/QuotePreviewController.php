<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Rate\QuotePreviewRequest;
use App\Http\Resources\Public\QuoteResource;
use App\Models\Apartment;
use App\Services\Booking\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Aperçu du prix d'un séjour test sur l'écran Tarifs. */
class QuotePreviewController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function __invoke(QuotePreviewRequest $request, Apartment $apartment): JsonResponse
    {
        Gate::authorize('view', $apartment);

        ['quote' => $quote, 'warnings' => $warnings] = $this->quotes->preview($apartment, $request->stay());

        return response()->json(['data' => [
            'quote' => $quote ? (new QuoteResource($quote))->resolve($request) : null,
            'warnings' => $warnings,
        ]]);
    }
}
