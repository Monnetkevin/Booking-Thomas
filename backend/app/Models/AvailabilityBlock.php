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
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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
            'confirmed_by_payout' => 'boolean',
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

    /**
     * Fermeture posée sur des nuits déjà fermées, qu'aucun versement importé ne désigne comme
     * un séjour : ni conflit ni arrivée. Un versement de la plateforme arrivé ce jour-là (chargé
     * par withPayoutConfirmation) en refait un vrai conflit : c'était un séjour Booking.com.
     */
    public function isRedundantClosure(): bool
    {
        return $this->has_conflict && $this->is_redundant_closure && ! $this->getAttribute('confirmed_by_payout');
    }

    /** Conflit à montrer et à signaler. */
    public function isRealConflict(): bool
    {
        return $this->has_conflict && ! $this->isRedundantClosure();
    }

    /**
     * Ajoute confirmed_by_payout : un versement importé de la plateforme du calendrier arrive
     * le premier jour du bloc, dans le même logement.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithPayoutConfirmation(Builder $query): void
    {
        $query->select('availability_blocks.*')->selectSub($this->payoutExists(), 'confirmed_by_payout');
    }

    /**
     * Conflits à signaler : les fermetures redondantes non désignées comme séjours sont exclues.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRealConflicts(Builder $query): void
    {
        $query->where('has_conflict', true)
            ->where(fn (Builder $query) => $query->where('is_redundant_closure', false)->orWhereExists($this->payoutExists()));
    }

    /** Sous-requête : versement de la plateforme du calendrier arrivé le premier jour du bloc. */
    private function payoutExists(): QueryBuilder
    {
        return DB::table('platform_payouts')
            ->selectRaw('1')
            ->join('ical_feeds', 'ical_feeds.provider', '=', 'platform_payouts.platform')
            ->whereColumn('ical_feeds.id', 'availability_blocks.ical_feed_id')
            ->whereColumn('platform_payouts.apartment_id', 'availability_blocks.apartment_id')
            ->whereColumn('platform_payouts.arrival', 'availability_blocks.starts_on')
            ->limit(1);
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
