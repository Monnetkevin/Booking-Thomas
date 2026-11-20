<?php

namespace App\Services\Statistics;

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Compte les nuits d'une période [from, to[ à partir des blocs actifs : vendues en direct
 * (réservations confirmées ou terminées), vendues sur une plateforme (séjours importés),
 * fermées (blocages manuels). Une réservation en attente de paiement n'est pas une vente.
 */
class NightCounter
{
    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs (réservation chargée)
     * @return array{direct: int, platform: int, closed: int}
     */
    public function count(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $nights = ['direct' => 0, 'platform' => 0, 'closed' => 0];

        foreach ($blocks as $block) {
            $kind = $this->kind($block);
            if ($kind !== null) {
                $nights[$kind] += $this->overlap($block, $from, $to);
            }
        }

        return $nights;
    }

    /**
     * Nuits des séjours importés par iCal qui arrivent dans [from, to[, par plateforme
     * (« airbnb », « booking »), comptées comme leurs revenus : au mois d'arrivée.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs (calendrier chargé)
     * @return array<string, int>
     */
    public function platformNightsArriving(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $nights = [];
        foreach ($blocks as $block) {
            $provider = $block->type === BlockType::Ical ? $block->icalFeed?->provider : null;
            if ($provider !== null && $block->starts_on->gte($from) && $block->starts_on->lt($to)) {
                $nights[$provider->value] = ($nights[$provider->value] ?? 0) + (int) $block->starts_on->diffInDays($block->ends_on);
            }
        }

        return $nights;
    }

    /** Nuits du bloc qui tombent dans [from, to[. */
    public function overlap(AvailabilityBlock $block, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $start = $block->starts_on->max($from);
        $end = $block->ends_on->min($to);

        return $end->gt($start) ? (int) $start->diffInDays($end) : 0;
    }

    /** direct, platform, closed ; null pour une réservation qui n'est pas (ou plus) vendue. */
    public function kind(AvailabilityBlock $block): ?string
    {
        return match ($block->type) {
            BlockType::Ical => 'platform',
            BlockType::Manual => 'closed',
            BlockType::Booking => in_array($block->booking?->status, [BookingStatus::Confirmed, BookingStatus::Completed], true) ? 'direct' : null,
        };
    }

    /**
     * Taux d'occupation : nuits vendues sur nuits ouvertes (les nuits fermées ne comptent
     * pas comme disponibles). Null s'il n'y avait aucune nuit ouverte.
     *
     * @param  array{direct: int, platform: int, closed: int}  $nights
     */
    public function occupancy(array $nights, int $apartments, int $days): ?float
    {
        $open = $apartments * $days - $nights['closed'];

        return $open > 0 ? round(($nights['direct'] + $nights['platform']) / $open * 100, 1) : null;
    }

    /**
     * Séjours vendus (site et plateformes) qui arrivent dans [from, to[, et ceux du site.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return array{all: int, direct: int}
     */
    public function arrivals(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $kinds = $blocks
            ->filter(fn (AvailabilityBlock $block) => $block->starts_on->gte($from) && $block->starts_on->lt($to))
            ->map(fn (AvailabilityBlock $block) => $this->kind($block))
            ->filter(fn (?string $kind) => in_array($kind, ['direct', 'platform'], true));

        return ['all' => $kinds->count(), 'direct' => $kinds->filter(fn (string $kind) => $kind === 'direct')->count()];
    }

    /**
     * Nuits ouvertes à la vente : nuits de la période moins les fermetures.
     *
     * @param  array{direct: int, platform: int, closed: int}  $nights
     */
    public function openNights(array $nights, int $apartments, int $days): int
    {
        return $apartments * $days - $nights['closed'];
    }
}
