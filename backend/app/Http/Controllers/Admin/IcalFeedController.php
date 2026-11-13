<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ical\StoreIcalFeedRequest;
use App\Http\Requests\Admin\Ical\UpdateIcalFeedRequest;
use App\Http\Resources\Admin\IcalFeedResource;
use App\Http\Resources\Admin\IcalSyncLogResource;
use App\Models\Apartment;
use App\Models\IcalFeed;
use App\Services\Ical\IcalFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class IcalFeedController extends Controller
{
    public function __construct(private readonly IcalFeedService $feeds) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', IcalFeed::class);

        return IcalFeedResource::collection($this->feeds->list());
    }

    public function store(StoreIcalFeedRequest $request, Apartment $apartment): JsonResponse
    {
        Gate::authorize('create', IcalFeed::class);

        $feed = $this->feeds->create($apartment, $request->provider(), $request->feedData()['import_url'] ?? null);

        return (new IcalFeedResource($feed))->response()->setStatusCode(201);
    }

    public function update(UpdateIcalFeedRequest $request, IcalFeed $icalFeed): IcalFeedResource
    {
        Gate::authorize('update', $icalFeed);

        return new IcalFeedResource($this->feeds->update($icalFeed, $request->feedData()));
    }

    /** « Synchroniser maintenant » : lecture immédiate, bilan renvoyé à l'hôte. */
    public function sync(IcalFeed $icalFeed): IcalSyncLogResource
    {
        Gate::authorize('update', $icalFeed);

        return new IcalSyncLogResource($this->feeds->syncNow($icalFeed));
    }

    public function logs(IcalFeed $icalFeed): AnonymousResourceCollection
    {
        Gate::authorize('view', $icalFeed);

        return IcalSyncLogResource::collection($this->feeds->logs($icalFeed));
    }

    public function regenerateExportToken(IcalFeed $icalFeed): IcalFeedResource
    {
        Gate::authorize('update', $icalFeed);

        return new IcalFeedResource($this->feeds->regenerateExportToken($icalFeed));
    }
}
