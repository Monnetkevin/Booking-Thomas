<?php

namespace App\Services\Planning;

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;

/**
 * Planning multi-logements (spec « Back-office › Planning ») : pour chaque logement, les
 * nuits occupées sur la période — réservations directes (avec leur statut), séjours
 * importés, fermetures manuelles, et les conflits ; avec les chiffres de la période.
 */
class PlanningService
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly PlanningFigures $figures,
        private readonly SoldStayBlocks $soldStays,
    ) {}

    /**
     * @return array{from: string, to: string, figures: array<string, mixed>, apartments: list<array{id: int, name: string, blocks: list<array<string, mixed>>}>}
     */
    public function build(CarbonImmutable $from, int $days): array
    {
        $to = $from->addDays($days);
        $apartments = $this->apartments->allOrdered();
        $ids = $apartments->modelKeys();
        $all = $this->availability->planningBlocks($from->toDateString(), $to->toDateString())->whereIn('apartment_id', $ids);
        $blocks = $all->groupBy('apartment_id');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            // Chiffres : une date Booking.com qui répète une fermeture du site compte comme fermée.
            'figures' => $this->figures->compute($this->soldStays->classify($all->toBase()), $apartments, $from, $to),
            'apartments' => $apartments->map(fn (Apartment $apartment) => [
                'id' => $apartment->id,
                'name' => $apartment->getTranslation('name', 'fr'),
                'blocks' => $blocks->get($apartment->id, collect())->map(fn (AvailabilityBlock $block) => $this->block($block))->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function block(AvailabilityBlock $block): array
    {
        $booking = $block->booking;

        return [
            'id' => $block->id,
            'type' => $block->type->value,
            'starts_on' => $block->starts_on->toDateString(),
            'ends_on' => $block->ends_on->toDateString(),
            'has_conflict' => $block->isRealConflict(),
            // Fermeture posée sur des nuits déjà fermées : affichée en retrait, sans alerte.
            'redundant_closure' => $block->isRedundantClosure(),
            // Fermeture d'un logement occupé : ménage à prévoir à la fin.
            'needs_cleaning' => $block->needs_cleaning,
            'label' => match ($block->type) {
                BlockType::Booking => $booking?->guest_name,
                BlockType::Ical => $block->icalFeed?->provider->label(),
                BlockType::Manual => $block->note,
            },
            'booking' => $booking ? [
                'reference' => $booking->reference,
                'status' => $booking->status->value,
                'source' => $booking->source->value,
                'guests' => $booking->adults + $booking->children,
            ] : null,
            'external_url' => $block->type === BlockType::Ical ? $block->external_url : null,
        ];
    }
}
