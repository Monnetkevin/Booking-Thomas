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
 * @property bool $needs_cleaning fermeture manuelle, logement occupé : ménage à prévoir à la fin
 * @property bool $is_redundant_closure fermeture posée sur des nuits déjà fermées : pas un conflit
 */
#[Fillable([
    'apartment_id', 'starts_on', 'ends_on', 'type', 'booking_id', 'ical_feed_id',
    'external_uid', 'external_url', 'note', 'needs_cleaning', 'is_active', 'has_conflict', 'is_redundant_closure',
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
            'needs_cleaning' => 'boolean',
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

    /** Fermeture posée sur des nuits déjà fermées : ni conflit ni arrivée. */
    public function isRedundantClosure(): bool
    {
        return $this->has_conflict && $this->is_redundant_closure;
    }

    /**
     * Fermeture certaine : blocage manuel, ou date fermée sur une plateforme qui les distingue
     * (Airbnb « Not available »). Un bloc Booking.com peut être un séjour : jamais certain.
     */
    public function isClosure(): bool
    {
        return match ($this->type) {
            BlockType::Manual => true,
            BlockType::Booking => false,
            BlockType::Ical => $this->icalFeed?->provider->isClosure($this->note) ?? false,
        };
    }

    /**
     * Le ménage doit passer à la fin : réservation, séjour importé (ou possible séjour
     * Booking.com), fermeture manuelle d'un logement occupé. Pas une date fermée sans
     * occupant, ni une fermeture posée sur des nuits déjà fermées.
     */
    public function concernsHousekeeping(): bool
    {
        if ($this->type === BlockType::Manual) {
            return (bool) $this->needs_cleaning;
        }

        return ! $this->isClosure() && ! $this->isRedundantClosure();
    }

    /** Ce que le ménage sait de ce bloc, avant une modification : séjour à son planning ou non. */
    public function knownToHousekeeping(): bool
    {
        return $this->is_active && $this->concernsHousekeeping();
    }

    /** Conflit à montrer et à signaler. */
    public function isRealConflict(): bool
    {
        return $this->has_conflict && ! $this->isRedundantClosure();
    }

    /**
     * Conflits à signaler : les fermetures redondantes non désignées comme séjours sont exclues.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRealConflicts(Builder $query): void
    {
        $query->where('has_conflict', true)->withoutRedundantClosures();
    }

    /**
     * Sans les fermetures posées sur des nuits déjà fermées.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithoutRedundantClosures(Builder $query): void
    {
        $query->where('is_redundant_closure', false);
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
