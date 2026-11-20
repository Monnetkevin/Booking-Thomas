<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Models\Booking;
use App\Services\Statistics\StatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Statistiques (6 mois, 12 mois ou tout) et leur export CSV. */
class StatisticsController extends Controller
{
    public function __construct(private readonly StatisticsService $statistics) {}

    public function show(StatisticsRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Booking::class);

        return response()->json(['data' => $this->statistics->report(CarbonImmutable::today(), $request->period())]);
    }

    public function csv(StatisticsRequest $request): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $today = CarbonImmutable::today();

        return response($this->statistics->csv($today, $request->period()), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="statistiques-'.$today->format('Y-m-d').'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
