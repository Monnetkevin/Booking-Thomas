<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Planning\PlanningRequest;
use App\Models\AvailabilityBlock;
use App\Services\Planning\PlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Planning multi-logements de l'hôte. */
class PlanningController extends Controller
{
    public function __construct(private readonly PlanningService $planning) {}

    public function __invoke(PlanningRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', AvailabilityBlock::class);

        return response()->json(['data' => $this->planning->build($request->from(), $request->days())]);
    }
}
