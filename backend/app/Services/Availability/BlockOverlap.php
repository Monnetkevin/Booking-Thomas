<?php

namespace App\Services\Availability;

use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/** Blocs actifs (hors conflit) qui occupent déjà des nuits demandées par un bloc importé. */
final readonly class BlockOverlap
{
    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  avec leur réservation et leur calendrier chargés
     */
    public function __construct(public Collection $blocks) {}

    /** Le nouveau bloc doit être enregistré en conflit (hors contrainte d'exclusion). */
    public function isConflict(): bool
    {
        return $this->blocks->isNotEmpty();
    }

    /**
     * Toutes les nuits [from, to[ sont déjà tenues par des fermetures, et rien d'autre ne les
     * recouvre : un bloc qui n'est lui-même qu'une fermeture n'y change rien.
     */
    public function onlyClosuresCovering(CarbonImmutable $from, CarbonImmutable $to): bool
    {
        if (! $this->isConflict() || ! $this->blocks->every(fn (AvailabilityBlock $block) => $block->mayBeClosure())) {
            return false;
        }
        for ($day = $from; $day->lt($to); $day = $day->addDay()) {
            if (! $this->blocks->contains(fn (AvailabilityBlock $block) => $block->starts_on->lte($day) && $block->ends_on->gt($day))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Réservations directes encore en attente de paiement sur ces nuits.
     *
     * @return Collection<int, Booking>
     */
    public function pendingBookings(): Collection
    {
        return $this->bookings()->filter(fn (Booking $booking) => $booking->status === BookingStatus::Pending)->values();
    }

    /**
     * Réservations directes confirmées (ou terminées) sur ces nuits : un vrai double
     * séjour, à signaler à l'hôte.
     *
     * @return Collection<int, Booking>
     */
    public function confirmedBookings(): Collection
    {
        return $this->bookings()->filter(fn (Booking $booking) => $booking->status !== BookingStatus::Pending)->values();
    }

    /**
     * @return Collection<int, Booking>
     */
    private function bookings(): Collection
    {
        return new Collection($this->blocks->map(fn (AvailabilityBlock $block) => $block->booking)->filter()->values()->all());
    }
}
