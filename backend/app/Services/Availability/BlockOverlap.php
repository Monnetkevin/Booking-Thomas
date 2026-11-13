<?php

namespace App\Services\Availability;

use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

/** Blocs actifs (hors conflit) qui occupent déjà des nuits demandées par un nouveau bloc. */
final readonly class BlockOverlap
{
    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  avec leur réservation chargée
     */
    public function __construct(public Collection $blocks) {}

    /** Le nouveau bloc doit être enregistré en conflit (hors contrainte d'exclusion). */
    public function isConflict(): bool
    {
        return $this->blocks->isNotEmpty();
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
