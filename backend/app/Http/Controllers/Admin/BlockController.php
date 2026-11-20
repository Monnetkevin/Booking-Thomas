<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blocks\StoreBlockRequest;
use App\Http\Resources\Admin\BlockResource;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Services\Availability\ManualBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Fermetures manuelles d'un logement (travaux, usage personnel). */
class BlockController extends Controller
{
    public function __construct(private readonly ManualBlockService $blocks) {}

    public function store(StoreBlockRequest $request, Apartment $apartment): JsonResponse
    {
        Gate::authorize('create', AvailabilityBlock::class);

        $block = $this->blocks->create($apartment, $request->startsOn(), $request->endsOn(), $request->note());

        return (new BlockResource($block))->response()->setStatusCode(201);
    }

    public function destroy(AvailabilityBlock $block): Response
    {
        Gate::authorize('delete', $block);

        $this->blocks->delete($block);

        return response()->noContent();
    }
}
