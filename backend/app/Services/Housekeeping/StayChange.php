<?php

namespace App\Services\Housekeeping;

use App\Enums\StayChangeKind;
use App\Models\AvailabilityBlock;
use Carbon\CarbonImmutable;

/**
 * Changement d'un séjour au planning du ménage : bloc concerné, nuits [arrivée, départ[,
 * anciennes nuits s'il est déplacé, nombre de voyageurs s'il est connu (réservation directe).
 */
final readonly class StayChange
{
    public function __construct(
        public StayChangeKind $kind,
        public int $blockId,
        public int $apartmentId,
        public CarbonImmutable $startsOn,
        public CarbonImmutable $endsOn,
        public ?CarbonImmutable $previousStartsOn = null,
        public ?CarbonImmutable $previousEndsOn = null,
        public ?int $guests = null,
    ) {}

    public static function added(AvailabilityBlock $block, ?int $guests = null): self
    {
        return new self(StayChangeKind::Added, $block->id, $block->apartment_id, $block->starts_on, $block->ends_on, guests: $guests);
    }

    public static function cancelled(AvailabilityBlock $block): self
    {
        return new self(StayChangeKind::Cancelled, $block->id, $block->apartment_id, $block->starts_on, $block->ends_on);
    }

    public static function moved(AvailabilityBlock $block, CarbonImmutable $previousStartsOn, CarbonImmutable $previousEndsOn): self
    {
        return new self(StayChangeKind::Moved, $block->id, $block->apartment_id, $block->starts_on, $block->ends_on, $previousStartsOn, $previousEndsOn);
    }

    /**
     * Jours où le ménage est concerné : arrivées et départs, anciens compris.
     *
     * @return list<CarbonImmutable>
     */
    public function days(): array
    {
        return array_values(array_filter([$this->startsOn, $this->endsOn, $this->previousStartsOn, $this->previousEndsOn]));
    }
}
