<?php

namespace App\Services\Housekeeping;

use App\Enums\CleaningMovementKind;
use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Planning du ménage (spec « Second rôle : ménage ») : jour par jour, les départs à
 * nettoyer, les arrivées avec leur heure, les fermetures, logement par logement dans
 * l'ordre du site. Mêmes règles que le récapitulatif du lendemain (CleaningMovement).
 */
class CleaningScheduleService
{
    /** Ce qui libère le logement (ménage à faire) et ce qui l'occupe ensuite. */
    private const LEAVING = [CleaningMovementKind::Departure, CleaningMovementKind::OccupiedEnd];

    private const COMING = [CleaningMovementKind::Arrival, CleaningMovementKind::Occupied];

    public function __construct(private readonly AvailabilityRepositoryInterface $availability) {}

    /**
     * @return array{from: string, to: string, days: list<array{date: string, movements: list<array<string, mixed>>}>}
     */
    public function build(CarbonImmutable $from, int $days): array
    {
        $to = $from->addDays($days - 1);
        $blocks = $this->availability->movementsBetween($from->toDateString(), $to->toDateString());

        $schedule = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $movements = $this->movementsOn($blocks->all(), $date);
            if ($movements !== []) {
                $schedule[] = ['date' => $date->toDateString(), 'movements' => $movements];
            }
        }

        return ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $schedule];
    }

    /**
     * @param  array<int, AvailabilityBlock>  $blocks
     * @return list<array<string, mixed>>
     */
    private function movementsOn(array $blocks, CarbonImmutable $date): array
    {
        $movements = [];
        foreach ($blocks as $block) {
            if (! $block->starts_on->isSameDay($date) && ! $block->ends_on->isSameDay($date)) {
                continue;
            }
            $movement = CleaningMovement::fromBlock($block, $date);
            if ($movement !== null) {
                // Ordre du site, puis départs avant arrivées.
                $movements[] = ['position' => $block->apartment->position, 'movement' => $movement];
            }
        }
        usort($movements, fn (array $a, array $b) => [$a['position'], $a['movement']->apartmentId, $this->rank($a['movement'])]
            <=> [$b['position'], $b['movement']->apartmentId, $this->rank($b['movement'])]);

        $list = array_map(fn (array $entry) => $entry['movement'], $movements);
        $kinds = [];
        foreach ($list as $movement) {
            $kinds[$movement->apartmentId][] = $movement->kind;
        }

        return array_map(fn (CleaningMovement $movement) => [
            'apartment_id' => $movement->apartmentId,
            'apartment' => $movement->apartmentName,
            'kind' => $movement->kind->value,
            'time' => $movement->time,
            'guests' => $movement->guests,
            'notes' => $movement->notes,
            'same_day_turnover' => $this->isTurnover($kinds[$movement->apartmentId]),
        ], $list);
    }

    private function rank(CleaningMovement $movement): int
    {
        return in_array($movement->kind, self::LEAVING, true) ? 0 : 1;
    }

    /**
     * Départ et arrivée le même jour dans ce logement : ménage à faire entre les deux heures.
     *
     * @param  list<CleaningMovementKind>  $kinds  mouvements du logement ce jour-là
     */
    private function isTurnover(array $kinds): bool
    {
        $any = fn (array $wanted) => array_filter($kinds, fn (CleaningMovementKind $kind) => in_array($kind, $wanted, true)) !== [];

        return $any(self::LEAVING) && $any(self::COMING);
    }
}
