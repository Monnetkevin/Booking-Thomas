<?php

namespace App\Services\Availability;

use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Conflits de dates entre blocs. Un bloc importé d'une plateforme qui recouvre des nuits
 * déjà occupées est enregistré en conflit : la contrainte d'exclusion ne s'applique pas à
 * lui (il ne peut pas être refusé, la réservation existe déjà chez la plateforme).
 * À appeler sous le verrou du logement (AvailabilityRepositoryInterface::lockApartment).
 */
class BlockConflictService
{
    public function __construct(private readonly AvailabilityRepositoryInterface $availability) {}

    /** Nuits [from, to[ déjà occupées par d'autres blocs actifs ? */
    public function check(int $apartmentId, CarbonImmutable $from, CarbonImmutable $to, ?int $exceptBlockId = null): BlockOverlap
    {
        return new BlockOverlap(
            $this->availability->overlappingActive($apartmentId, $from->toDateString(), $to->toDateString(), $exceptBlockId),
        );
    }

    /**
     * Des nuits [from, to[ viennent d'être libérées (réservation expirée ou annulée,
     * blocage retiré, événement disparu du flux) : chaque bloc en conflit sur ces nuits
     * qui ne recouvre plus rien redevient un bloc normal, l'un après l'autre (deux blocs
     * en conflit entre eux : le plus ancien l'emporte, l'autre reste en conflit).
     *
     * @return int nombre de conflits levés
     */
    public function release(int $apartmentId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $resolved = 0;

        foreach ($this->availability->conflictsOverlapping($apartmentId, $from->toDateString(), $to->toDateString()) as $block) {
            /** @var AvailabilityBlock $block */
            if (! $this->check($apartmentId, $block->starts_on, $block->ends_on, $block->id)->isConflict()) {
                $this->availability->updateBlock($block, ['has_conflict' => false]);
                $resolved++;
            }
        }

        return $resolved;
    }
}
