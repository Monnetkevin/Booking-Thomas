<?php

namespace App\Repositories\Eloquent;

use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityRepository implements AvailabilityRepositoryInterface
{
    public function hasActiveBlock(int $apartmentId, string $from, string $to): bool
    {
        return $this->query($apartmentId, $from, $to)->exists();
    }

    public function activeBlocks(int $apartmentId, string $from, string $to): Collection
    {
        return $this->query($apartmentId, $from, $to)->orderBy('starts_on')->get(['id', 'starts_on', 'ends_on']);
    }

    /**
     * @return Builder<AvailabilityBlock>
     */
    private function query(int $apartmentId, string $from, string $to): Builder
    {
        return AvailabilityBlock::query()
            ->where('apartment_id', $apartmentId)
            ->where('is_active', true)
            ->where('has_conflict', false)
            ->overlapping($from, $to);
    }
}
