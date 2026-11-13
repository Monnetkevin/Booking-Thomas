<?php

namespace App\Repositories\Eloquent;

use App\Enums\IcalProvider;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class IcalFeedRepository implements IcalFeedRepositoryInterface
{
    public function importable(): Collection
    {
        return IcalFeed::query()->where('is_active', true)->whereNotNull('import_url')->orderBy('id')->get();
    }

    public function all(): Collection
    {
        return IcalFeed::query()->with('apartment')->orderBy('apartment_id')->orderBy('provider')->get();
    }

    public function findByExportToken(string $token): ?IcalFeed
    {
        return IcalFeed::query()->where('export_token', $token)->first();
    }

    public function exists(int $apartmentId, IcalProvider $provider): bool
    {
        return IcalFeed::query()->where('apartment_id', $apartmentId)->where('provider', $provider)->exists();
    }

    public function create(array $attributes): IcalFeed
    {
        return IcalFeed::query()->create($attributes);
    }

    public function update(IcalFeed $feed, array $attributes): IcalFeed
    {
        $feed->update($attributes);

        return $feed;
    }

    public function recordSync(IcalFeed $feed, array $feedAttributes, array $log): IcalSyncLog
    {
        $feed->update($feedAttributes);

        return $feed->syncLogs()->create($log);
    }

    public function recentLogs(IcalFeed $feed, int $limit): Collection
    {
        return $feed->syncLogs()->latest('id')->limit($limit)->get();
    }
}
