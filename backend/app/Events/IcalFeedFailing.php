<?php

namespace App\Events;

use App\Models\IcalFeed;
use Illuminate\Foundation\Events\Dispatchable;

/** Un calendrier vient d'échouer plusieurs fois de suite : ses nuits ne sont plus à jour. */
class IcalFeedFailing
{
    use Dispatchable;

    public function __construct(public readonly IcalFeed $feed) {}
}
