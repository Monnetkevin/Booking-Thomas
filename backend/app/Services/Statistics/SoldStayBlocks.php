<?php

namespace App\Services\Statistics;

use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Blocs actifs d'une période prêts à être comptés (NightCounter) : un bloc Booking.com n'y
 * est un séjour vendu que si un versement importé le confirme, sinon c'est une fermeture.
 * Import des revenus désactivé (config/features.php) : il est un séjour vendu, sauf s'il ne
 * fait que répéter une fermeture du site (isRedundantClosure).
 * Tableau de bord, planning, liste des logements et statistiques passent tous par ici, pour
 * que leurs chiffres concordent.
 */
class SoldStayBlocks
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly PlatformPayoutRepositoryInterface $payouts,
        private readonly NightCounter $nights,
        private readonly bool $payoutsEnabled = true,
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

        return $this->confirm($blocks->values(), $to);
    }

    /**
     * Applique la confirmation par versement à des blocs déjà chargés.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return Collection<int, AvailabilityBlock>
     */
    public function confirm(Collection $blocks, CarbonImmutable $to): Collection
    {
        $needsPayouts = $blocks->contains(fn (AvailabilityBlock $block) => $block->icalFeed?->provider->confirmsStaysByPayout() === true);
        if (! $needsPayouts) {
            return $blocks;
        }
        if (! $this->payoutsEnabled) {
            return $this->nights->closeRedundantClosures($blocks);
        }

        // Versements depuis le premier bloc : un séjour arrivé avant la période la chevauche.
        $firstStart = $blocks->min(fn (AvailabilityBlock $block) => $block->starts_on->toDateString());

        return $this->nights->confirmPlatformStays($blocks, $this->payouts->arrivingBetween(CarbonImmutable::parse($firstStart), $to));
    }
}
