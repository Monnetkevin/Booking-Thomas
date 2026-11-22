<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlatformRevenue\PlatformRevenueMonthRequest;
use App\Http\Requests\Admin\PlatformRevenue\SavePlatformRevenueRequest;
use App\Models\PlatformMonthlyRevenue;
use App\Services\PlatformRevenue\PlatformRevenueService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Revenus Airbnb et Booking.com saisis chaque mois : feuille d'un mois, saisie, historique. */
class PlatformRevenueController extends Controller
{
    public function __construct(private readonly PlatformRevenueService $revenues) {}

    public function show(PlatformRevenueMonthRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', PlatformMonthlyRevenue::class);

        return response()->json(['data' => $this->revenues->sheet($request->month(), CarbonImmutable::today())]);
    }

    public function update(SavePlatformRevenueRequest $request): JsonResponse
    {
        Gate::authorize('update', PlatformMonthlyRevenue::class);

        return response()->json(['data' => $this->revenues->save($request->month(), $request->entries(), $request->user()?->id, CarbonImmutable::today())]);
    }

    public function history(): JsonResponse
    {
        Gate::authorize('viewAny', PlatformMonthlyRevenue::class);

        return response()->json(['data' => $this->revenues->history(CarbonImmutable::today())]);
    }
}
