<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Models\Booking;
use App\Services\Dashboard\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Tableau de bord de l'hôte. */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(DashboardRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Booking::class);

        return response()->json(['data' => $this->dashboard->summary(CarbonImmutable::today(), $request->period())]);
    }
}
