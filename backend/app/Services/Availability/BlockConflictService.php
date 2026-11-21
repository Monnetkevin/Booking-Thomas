<?php

namespace App\Services\Availability;

use App\Enums\BlockType;
use App\Enums\PayoutPlatform;
use App\Events\StayScheduleChanged;
use App\Models\AvailabilityBlock;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Housekeeping\StayChange;
use Carbon\CarbonImmutable;

/**
 * Conflits de dates entre blocs. Un bloc importé d'une plateforme qui recouvre des nuits
 * déjà occupées est enregistré en conflit : la contrainte d'exclusion ne s'applique pas à
 * lui (il ne peut pas être refusé, la réservation existe déjà chez la plateforme). Une
 * fermeture posée sur des nuits déjà fermées n'est pas un vrai conflit (is_redundant_closure).
 * À appeler sous le verrou du logement (AvailabilityRepositoryInterface::lockApartment).
 */
class BlockConflictService
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly PlatformPayoutRepositoryInterface $payouts,
    ) {}

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
     * Un bloc qui n'était qu'une fermeture redondante et redevient un séjour (fermeture du
     * site retirée sous une date Booking.com), ou l'inverse, est signalé au ménage.
     *
     * @return int nombre de conflits levés
     */
    public function release(int $apartmentId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $resolved = 0;

        foreach ($this->availability->conflictsOverlapping($apartmentId, $from->toDateString(), $to->toDateString()) as $block) {
            /** @var AvailabilityBlock $block */
            $housekeeping = $block->knownToHousekeeping();
            $overlap = $this->check($apartmentId, $block->starts_on, $block->ends_on, $block->id);
            if (! $overlap->isConflict()) {
                $this->availability->updateBlock($block, ['has_conflict' => false, 'is_redundant_closure' => false]);
                $resolved++;
            } elseif (($redundant = $this->isRedundantClosure($block, $overlap)) !== $block->is_redundant_closure) {
                // Il ne reste que des fermetures sous ce bloc (ou de nouveau un séjour).
                $this->availability->updateBlock($block, ['is_redundant_closure' => $redundant]);
            }
            $this->notifyHousekeeping($block, $housekeeping);
        }

        return $resolved;
    }

    /**
     * Le bloc, mêmes nuits, vient de changer de nature pour le ménage (séjour ↔ simple date
     * fermée) : séjour ajouté ou annulé à son planning.
     */
    public function notifyHousekeeping(AvailabilityBlock $block, bool $before): void
    {
        $after = $block->is_active && $block->concernsHousekeeping();
        if ($after !== $before) {
            StayScheduleChanged::dispatch($after ? StayChange::added($block) : StayChange::cancelled($block));
        }
    }

    /**
     * Le bloc importé (non enregistré, ou déjà là) n'est qu'une fermeture posée sur des nuits
     * toutes déjà fermées : pas un conflit à signaler. Un bloc Booking.com (séjour ou fermeture,
     * on ne sait pas) dont un versement importé confirme l'arrivée reste un vrai conflit.
     */
    public function isRedundantClosure(AvailabilityBlock $block, BlockOverlap $overlap): bool
    {
        if (! $block->mayBeClosure() || ! $overlap->onlyClosuresCovering($block->starts_on, $block->ends_on)) {
            return false;
        }
        $provider = $block->type === BlockType::Ical ? $block->icalFeed?->provider : null;

        return $provider === null || ! $provider->confirmsStaysByPayout()
            || ! $this->payouts->hasArrival(PayoutPlatform::from($provider->value), $block->apartment_id, $block->starts_on->toDateString());
    }
}
