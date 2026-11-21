<?php

namespace App\Models;

use App\Enums\BlockType;
use Carbon\CarbonImmutable;
use Database\Factories\AvailabilityBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Source unique de vérité des nuits occupées, quelle qu'en soit l'origine.
 * Plage [starts_on, ends_on[ ; PostgreSQL refuse deux blocs actifs qui se recouvrent.
 *
 * @property int $id
 * @property int $apartment_id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property BlockType $type
 * @property int|null $booking_id
 * @property int|null $ical_feed_id
 * @property string|null $external_uid
 * @property string|null $external_url
 * @property string|null $note
 * @property bool $is_active
 * @property bool $has_conflict
 * @property bool $is_redundant_closure fermeture posée sur des nuits déjà fermées : pas un conflit
 */
#[Fillable([
    'apartment_id', 'starts_on', 'ends_on', 'type', 'booking_id', 'ical_feed_id',
    'external_uid', 'external_url', 'note', 'is_active', 'has_conflict', 'is_redundant_closure',
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
            'is_redundant_closure' => 'boolean',
        ];
    }

    /**
     * Peut être une simple fermeture : blocage manuel, date fermée sur une plateforme, ou bloc
     * Booking.com (séjour et fermeture s'y confondent). Une réservation du site n'en est jamais une.
     */
    public function mayBeClosure(): bool
    {
        return match ($this->type) {
            BlockType::Manual => true,
            BlockType::Booking => false,
            BlockType::Ical => $this->icalFeed?->provider->mayBeClosure($this->note) ?? false,
        };
    }

    /** Conflit à montrer et à signaler (une fermeture redondante n'en est pas un). */
    public function isRealConflict(): bool
    {
        return $this->has_conflict && ! $this->is_redundant_closure;
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
        // Logement supprimé compris : ses séjours et calendriers restent lisibles (notifications).
        return $this->belongsTo(Apartment::class)->withTrashed();
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
