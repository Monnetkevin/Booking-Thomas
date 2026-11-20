<?php

namespace App\Services\Planning;

use App\Models\AvailabilityBlock;
use App\Services\Statistics\NightCounter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Chiffres de la période affichée au planning, avec les mêmes règles que le tableau de bord
 * et les statistiques (NightCounter) : nuits vendues et ouvertes, occupation, arrivées dont
 * directes, nuits libres dont de week-end (vendredi et samedi).
 */
class PlanningFigures
{
    public function __construct(private readonly NightCounter $nights) {}

    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs des logements affichés
     * @param  list<int>  $apartmentIds
     * @return array{sold_nights: int, open_nights: int, occupancy: float|null, arrivals: int, direct_arrivals: int, free_nights: int, free_weekend_nights: int}
     */
    public function compute(Collection $blocks, array $apartmentIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = (int) $from->diffInDays($to);
        $nights = $this->nights->count($blocks, $from, $to);
        $arrivals = $blocks
            ->filter(fn (AvailabilityBlock $block) => $block->starts_on->gte($from) && $block->starts_on->lt($to))
            ->map(fn (AvailabilityBlock $block) => $this->nights->kind($block))
            ->filter(fn (?string $kind) => in_array($kind, ['direct', 'platform'], true));

        $free = 0;
        $freeWeekend = 0;
        foreach ($apartmentIds as $apartmentId) {
            $own = $blocks->filter(fn (AvailabilityBlock $block) => $block->apartment_id === $apartmentId);
            for ($day = $from; $day->lt($to); $day = $day->addDay()) {
                $taken = $own->contains(fn (AvailabilityBlock $block) => $block->starts_on->lte($day) && $block->ends_on->gt($day));
                if (! $taken) {
                    $free++;
                    $freeWeekend += in_array($day->dayOfWeek, [CarbonImmutable::FRIDAY, CarbonImmutable::SATURDAY], true) ? 1 : 0;
                }
            }
        }

        return [
            'sold_nights' => $nights['direct'] + $nights['platform'],
            'open_nights' => count($apartmentIds) * $days - $nights['closed'],
            'occupancy' => $this->nights->occupancy($nights, count($apartmentIds), $days),
            'arrivals' => $arrivals->count(),
            'direct_arrivals' => $arrivals->filter(fn (string $kind) => $kind === 'direct')->count(),
            'free_nights' => $free,
            'free_weekend_nights' => $freeWeekend,
        ];
    }
}
