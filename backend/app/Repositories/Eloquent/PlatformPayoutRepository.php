<?php

namespace App\Repositories\Eloquent;

use App\Enums\PayoutPlatform;
use App\Models\PlatformImport;
use App\Models\PlatformListing;
use App\Models\PlatformPayout;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class PlatformPayoutRepository implements PlatformPayoutRepositoryInterface
{
    public function listingMap(PayoutPlatform $platform): array
    {
        /** @var array<string, int> $map */
        $map = PlatformListing::query()->where('platform', $platform)->pluck('apartment_id', 'listing')->all();

        return $map;
    }

    public function saveListingMap(PayoutPlatform $platform, array $map): void
    {
        foreach ($map as $listing => $apartmentId) {
            PlatformListing::query()->updateOrCreate(['platform' => $platform, 'listing' => $listing], ['apartment_id' => $apartmentId]);
        }
    }

    public function existingCodes(PayoutPlatform $platform, array $codes): array
    {
        /** @var list<string> $existing */
        $existing = PlatformPayout::query()->where('platform', $platform)->whereIn('confirmation_code', $codes)->pluck('confirmation_code')->all();

        return $existing;
    }

    public function createImport(array $attributes): PlatformImport
    {
        return PlatformImport::query()->create($attributes);
    }

    public function updateImport(PlatformImport $import, array $attributes): PlatformImport
    {
        $import->update($attributes);

        return $import;
    }

    public function upsert(PayoutPlatform $platform, string $code, array $attributes): bool
    {
        $payout = PlatformPayout::query()->updateOrCreate(['platform' => $platform, 'confirmation_code' => $code], $attributes);

        return $payout->wasRecentlyCreated;
    }

    public function recentImports(int $limit): Collection
    {
        return PlatformImport::query()->with('user:id,name')->latest('id')->limit($limit)->get();
    }

    public function arrivingBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return PlatformPayout::query()
            ->where('arrival', '>=', $from->toDateString())
            ->where('arrival', '<', $to->toDateString())
            ->get();
    }
}
