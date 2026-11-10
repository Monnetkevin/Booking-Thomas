<?php

namespace App\Models;

use App\Enums\BlockType;
use Database\Factories\AvailabilityBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Source unique de vérité des nuits occupées, quelle qu'en soit l'origine.
 * Plage [starts_on, ends_on[ ; PostgreSQL refuse deux blocs actifs qui se recouvrent.
 */
#[Fillable([
    'apartment_id', 'starts_on', 'ends_on', 'type', 'booking_id', 'ical_feed_id',
    'external_uid', 'note', 'is_active', 'has_conflict',
])]
class AvailabilityBlock extends Model
{
    /** @use HasFactory<AvailabilityBlockFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'type' => BlockType::class,
            'is_active' => 'boolean',
            'has_conflict' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Blocs qui touchent au moins une nuit de [from, to[.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, string $from, string $to): void
    {
        $query->where('starts_on', '<', $to)->where('ends_on', '>', $from);
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<IcalFeed, $this> */
    public function icalFeed(): BelongsTo
    {
        return $this->belongsTo(IcalFeed::class);
    }
}
