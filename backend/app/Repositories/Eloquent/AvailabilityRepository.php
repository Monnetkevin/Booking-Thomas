<?php

namespace App\Repositories\Eloquent;

use App\Enums\BlockType;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AvailabilityRepository implements AvailabilityRepositoryInterface
{
    /** Espace de noms des verrous consultatifs PostgreSQL réservés aux logements. */
    private const LOCK_NAMESPACE = 4201;

    public function hasActiveBlock(int $apartmentId, string $from, string $to): bool
    {
        return $this->query($apartmentId, $from, $to)->exists();
    }

    public function activeBlocks(int $apartmentId, string $from, string $to): Collection
    {
        return $this->query($apartmentId, $from, $to)->orderBy('starts_on')->get(['id', 'starts_on', 'ends_on']);
    }

    public function lockApartment(int $apartmentId): void
    {
        DB::select('SELECT pg_advisory_xact_lock(?, ?)', [self::LOCK_NAMESPACE, $apartmentId]);
    }

    public function blockForBooking(Booking $booking): AvailabilityBlock
    {
        return AvailabilityBlock::query()->create([
            'apartment_id' => $booking->apartment_id,
            'booking_id' => $booking->id,
            'starts_on' => $booking->arrival->toDateString(),
            'ends_on' => $booking->departure->toDateString(),
            'type' => BlockType::Booking,
        ]);
    }

    public function releaseBooking(Booking $booking): void
    {
        AvailabilityBlock::query()->where('booking_id', $booking->id)->update(['is_active' => false]);
    }

    public function overlappingActive(int $apartmentId, string $from, string $to, ?int $exceptBlockId = null): Collection
    {
        return $this->query($apartmentId, $from, $to)
            ->when($exceptBlockId !== null, fn (Builder $query) => $query->whereKeyNot($exceptBlockId))
            ->with('booking')
            ->orderBy('starts_on')
            ->get();
    }

    public function feedBlocks(int $feedId): Collection
    {
        return AvailabilityBlock::query()->where('ical_feed_id', $feedId)->get();
    }

    public function createBlock(array $attributes): AvailabilityBlock
    {
        return AvailabilityBlock::query()->create($attributes);
    }

    public function updateBlock(AvailabilityBlock $block, array $attributes): AvailabilityBlock
    {
        $block->update($attributes);

        return $block;
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
