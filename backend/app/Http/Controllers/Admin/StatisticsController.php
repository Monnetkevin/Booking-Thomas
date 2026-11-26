<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Http\Responses\CsvDownload;
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

        return CsvDownload::make($this->statistics->csv(CarbonImmutable::today(), $request->period()), 'statistiques');
    }
}
