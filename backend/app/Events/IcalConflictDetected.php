<?php

namespace App\Events;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Une plateforme vient de transmettre un séjour qui recouvre des réservations directes
 * confirmées : deux voyageurs attendus sur les mêmes nuits.
 */
class IcalConflictDetected implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    public function __construct(
        public readonly AvailabilityBlock $block,
        public readonly Collection $bookings,
    ) {}
}
