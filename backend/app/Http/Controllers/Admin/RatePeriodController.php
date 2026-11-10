<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Rate\IndexRatePeriodsRequest;
use App\Http\Requests\Admin\Rate\RatePeriodRequest;
use App\Http\Resources\Admin\RatePeriodResource;
use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Services\Rate\RatePeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RatePeriodController extends Controller
{
    public function __construct(private readonly RatePeriodService $periods) {}

    public function index(IndexRatePeriodsRequest $request, Apartment $apartment): AnonymousResourceCollection
    {
        Gate::authorize('view', $apartment);

        return RatePeriodResource::collection($this->periods->list($apartment, $request->boolean('include_past')));
    }

    public function store(RatePeriodRequest $request, Apartment $apartment): JsonResponse
    {
        Gate::authorize('update', $apartment);

        $period = $this->periods->create($apartment, $request->periodData());

        return (new RatePeriodResource($period))->response()->setStatusCode(201);
    }

    public function update(RatePeriodRequest $request, RatePeriod $ratePeriod): RatePeriodResource
    {
        Gate::authorize('update', $ratePeriod);

        return new RatePeriodResource($this->periods->update($ratePeriod, $request->periodData()));
    }

    public function destroy(RatePeriod $ratePeriod): Response
    {
        Gate::authorize('delete', $ratePeriod);

        $this->periods->delete($ratePeriod);

        return response()->noContent();
    }
}
