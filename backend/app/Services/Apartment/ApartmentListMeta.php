<?php

namespace App\Services\Apartment;

use App\Enums\ApartmentStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;

/**
 * Compléments de la liste des logements du back-office : nombre par statut (filtres) et
 * taux d'occupation du mois en cours, tous canaux, de chaque logement publié.
 */
class ApartmentListMeta
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly SoldStayBlocks $soldStays,
        private readonly NightCounter $nights,
    ) {}

    /**
     * @return array{counts: array<string, int>, occupancy: array<int, float|null>}
     */
    public function compute(CarbonImmutable $today): array
    {
        $all = $this->apartments->allOrdered();
        $from = $today->startOfMonth();
        $to = $from->addMonth();
        $blocks = $this->soldStays->between($from, $to);

        $counts = ['all' => $all->count()];
        foreach (ApartmentStatus::cases() as $status) {
            $counts[$status->value] = $all->filter(fn (Apartment $apartment) => $apartment->status === $status)->count();
        }

        $occupancy = [];
        foreach ($all as $apartment) {
            $own = $blocks->filter(fn (AvailabilityBlock $block) => $block->apartment_id === $apartment->id);
            $occupancy[$apartment->id] = $apartment->status === ApartmentStatus::Published
                ? $this->nights->occupancy($this->nights->count($own, $from, $to), $this->nights->marketNights(collect([$apartment]), $from, $to))
                : null;
        }

        return ['counts' => $counts, 'occupancy' => $occupancy];
    }
}
