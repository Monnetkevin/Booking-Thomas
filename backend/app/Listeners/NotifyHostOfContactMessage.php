<?php

namespace App\Listeners;

use App\Events\ContactMessageReceived;
use App\Notifications\Host\ContactMessageNotification;
use App\Services\Notifications\NotificationDispatcher;

/** Nouveau message dans le back-office ; l'e-mail complet part déjà aux adresses du réglage. */
class NotifyHostOfContactMessage
{
    public function __construct(private readonly NotificationDispatcher $notifications) {}

    public function handle(ContactMessageReceived $event): void
    {
        $this->notifications->dispatch(new ContactMessageNotification($event->message));
    }
}
