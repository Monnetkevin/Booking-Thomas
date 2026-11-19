<?php

namespace App\Events;

use App\Services\Housekeeping\StayChange;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un séjour vient d'être ajouté, annulé ou déplacé au planning (import d'une plateforme). */
class StayScheduleChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly StayChange $change) {}
}
