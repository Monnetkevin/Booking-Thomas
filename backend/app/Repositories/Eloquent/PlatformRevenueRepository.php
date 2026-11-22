<?php

namespace App\Repositories\Eloquent;

use App\Enums\IcalProvider;
use App\Models\PlatformMonthlyRevenue;
use App\Repositories\Contracts\PlatformRevenueRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class PlatformRevenueRepository implements PlatformRevenueRepositoryInterface
{
    public function between(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return PlatformMonthlyRevenue::query()
            ->where('month', '>=', $from->toDateString())
            ->where('month', '<', $to->toDateString())
            ->with('user:id,name')
            ->orderBy('month')
            ->get();
    }

    public function put(IcalProvider $platform, int $apartmentId, CarbonImmutable $month, int $netAmount, ?int $userId): void
    {
        PlatformMonthlyRevenue::query()->updateOrCreate(
            ['platform' => $platform, 'apartment_id' => $apartmentId, 'month' => $month->toDateString()],
            ['net_amount' => $netAmount, 'user_id' => $userId],
        );
    }

    public function forget(IcalProvider $platform, int $apartmentId, CarbonImmutable $month): void
    {
        PlatformMonthlyRevenue::query()
            ->where('platform', $platform)
            ->where('apartment_id', $apartmentId)
            ->where('month', $month->toDateString())
            ->delete();
    }
}
