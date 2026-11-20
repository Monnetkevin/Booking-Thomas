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
            if ($apartmentId === null) {
                PlatformListing::query()->where('platform', $platform)->where('listing', (string) $listing)->delete();
            } else {
                PlatformListing::query()->updateOrCreate(['platform' => $platform, 'listing' => (string) $listing], ['apartment_id' => $apartmentId]);
            }
        }
    }

    public function existingKeys(PayoutPlatform $platform, array $keys): array
    {
        $existing = [];
        // Par paquets : PostgreSQL limite le nombre de paramètres d'une requête.
        foreach (array_chunk($keys, 1000) as $chunk) {
            /** @var list<string> $found */
            $found = PlatformPayout::query()->where('platform', $platform)->whereIn('entry_key', $chunk)->pluck('entry_key')->all();
            $existing = [...$existing, ...$found];
        }

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

    public function upsert(PayoutPlatform $platform, string $entryKey, array $attributes): bool
    {
        $payout = PlatformPayout::query()->updateOrCreate(['platform' => $platform, 'entry_key' => $entryKey], $attributes);

        return $payout->wasRecentlyCreated;
    }

    public function deleteKeys(PayoutPlatform $platform, array $keys): int
    {
        $removed = 0;
        foreach (array_chunk($keys, 1000) as $chunk) {
            $removed += PlatformPayout::query()->where('platform', $platform)->whereIn('entry_key', $chunk)->delete();
        }

        return $removed;
    }

    public function firstPayoutsByCode(PayoutPlatform $platform, array $codes): array
    {
        $stays = [];
        foreach (array_chunk($codes, 1000) as $chunk) {
            $payouts = PlatformPayout::query()->where('platform', $platform)->whereIn('confirmation_code', $chunk)
                ->orderBy('arrival')->orderBy('id')->get(['confirmation_code', 'arrival', 'nights', 'apartment_id']);
            foreach ($payouts as $payout) {
                $stays[$payout->confirmation_code] ??= [
                    'arrival' => $payout->arrival,
                    'nights' => (int) $payout->nights,
                    'apartment_id' => (int) $payout->apartment_id,
                ];
            }
        }

        return $stays;
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
