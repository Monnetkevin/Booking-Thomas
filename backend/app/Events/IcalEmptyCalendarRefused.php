<?php

namespace App\Events;

use App\Models\IcalFeed;
use Illuminate\Foundation\Events\Dispatchable;

/** Un calendrier vient d'être refusé parce que soudain vide (une fois, au passage au refus). */
class IcalEmptyCalendarRefused
{
    use Dispatchable;

    public function __construct(public readonly IcalFeed $feed) {}
}
