<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Notifications\BackOfficeNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;

/** Notification enregistrée pour le back-office du compte, regroupée par objet si non lue. */
class InAppChannel
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function send(User $notifiable, BackOfficeNotification $notification): void
    {
        $this->notifications->record($notifiable, [
            'id' => $notification->id,
            'type' => $notification->type(),
            'level' => $notification->level(),
            'dedupe_key' => $notification->dedupeKey(),
            'data' => $notification->toInApp(),
        ]);
    }
}
