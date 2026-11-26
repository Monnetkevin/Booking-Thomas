<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Extras\SaveExtraRequest;
use App\Http\Resources\Admin\ExtraResource;
use App\Models\Extra;
use App\Services\Extra\ExtraService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Options : catalogue et chiffres du mois, création, modification, liste « À préparer ». */
class ExtraController extends Controller
{
    public function __construct(private readonly ExtraService $extras) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Extra::class);

        return ExtraResource::collection($this->extras->catalog())
            ->additional(['meta' => ['figures' => $this->extras->figures(CarbonImmutable::today())]]);
    }

    public function store(SaveExtraRequest $request): JsonResponse
    {
        Gate::authorize('create', Extra::class);

        return (new ExtraResource($this->extras->create($request->extraAttributes(), $request->apartmentIds())))->response()->setStatusCode(201);
    }

    public function update(SaveExtraRequest $request, Extra $extra): ExtraResource
    {
        Gate::authorize('update', $extra);

        return new ExtraResource($this->extras->update($extra, $request->extraAttributes(), $request->apartmentIds()));
    }

    public function toPrepare(): JsonResponse
    {
        Gate::authorize('viewAny', Extra::class);

        return response()->json(['data' => $this->extras->toPrepare(CarbonImmutable::today())]);
    }
}
