<?php

namespace App\Events;

use App\Models\Review;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un voyageur vient de déposer son avis : à modérer. */
class ReviewSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Review $review) {}
}
