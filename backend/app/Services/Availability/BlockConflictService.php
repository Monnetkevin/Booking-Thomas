<?php

namespace App\Services\Availability;

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
}
