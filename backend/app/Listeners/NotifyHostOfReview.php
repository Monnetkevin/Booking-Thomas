<?php

namespace App\Listeners;

use App\Events\ReviewSubmitted;
use App\Notifications\Host\ReviewSubmittedNotification;
use App\Services\Notifications\NotificationDispatcher;

/** Nouvel avis à modérer dans le back-office (info). */
class NotifyHostOfReview
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function handle(ReviewSubmitted $event): void
    {
        $this->notifications->dispatch(new ReviewSubmittedNotification($event->review));
    }
}
