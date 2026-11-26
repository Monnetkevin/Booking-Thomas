<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

/** Chaque compte actif ne voit et ne marque que ses propres notifications. */
class NotificationPolicy
{
    /** Ses propres notifications : liste, nombre de non lues, tout marquer comme lu. */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->is_active
            && $notification->notifiable_type === $user->getMorphClass()
            && (int) $notification->notifiable_id === $user->id;
    }
}
