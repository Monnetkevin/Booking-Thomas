<?php

namespace App\Events;

use App\Models\IcalFeed;
use Illuminate\Foundation\Events\Dispatchable;

/** Un calendrier signalé en échec est de nouveau lisible. */
class IcalFeedRecovered
{
    use Dispatchable;

    public function __construct(public readonly IcalFeed $feed) {}
}
