<?php

namespace App\Services\Statistics;

use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Blocs actifs d'une période prêts à être comptés (NightCounter) : un bloc Booking.com (séjour
 * et fermeture s'y confondent) est un séjour vendu, sauf s'il ne fait que répéter une fermeture
 * du site (isRedundantClosure).
 * Tableau de bord, planning, liste des logements, statistiques et revenus des plateformes
 * passent tous par ici, pour que leurs chiffres concordent.
 */
class SoldStayBlocks
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly NightCounter $nights,
    ) {}

    /**
     * @param  list<int>|null  $apartmentIds  logements à garder (null : tous)
     * @return Collection<int, AvailabilityBlock>
     */
    public function between(CarbonImmutable $from, CarbonImmutable $to, ?array $apartmentIds = null): Collection
    {
        $blocks = $this->availability->planningBlocks($from->toDateString(), $to->toDateString());
        if ($apartmentIds !== null) {
            $blocks = $blocks->whereIn('apartment_id', $apartmentIds);
        }

        return $this->classify($blocks->values());
    }

    /**
     * Applique la même règle à des blocs déjà chargés.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return Collection<int, AvailabilityBlock>
     */
    public function classify(Collection $blocks): Collection
    {
        return $this->nights->closeRedundantClosures($blocks);
    }
}
