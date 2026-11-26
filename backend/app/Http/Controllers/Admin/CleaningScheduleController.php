<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Housekeeping\CleaningScheduleRequest;
use App\Models\AvailabilityBlock;
use App\Services\Housekeeping\CleaningScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Planning du ménage : départs, arrivées et fermetures des prochains jours. */
class CleaningScheduleController extends Controller
{
    public function __construct(private readonly CleaningScheduleService $schedule) {}

    public function __invoke(CleaningScheduleRequest $request): JsonResponse
    {
        Gate::authorize('viewCleaning', AvailabilityBlock::class);

        return response()->json(['data' => $this->schedule->build(CarbonImmutable::today(), $request->days())]);
    }
}
