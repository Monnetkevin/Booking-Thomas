<?php

namespace App\Services\Notifications;

use App\Notifications\BackOfficeNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Notifications\Dispatcher;

/**
 * Envoie une notification du back-office à tous les comptes actifs du rôle auquel son
 * type s'adresse (hôte ou ménage). Sans compte actif, rien n'est envoyé.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Dispatcher $notifier,
    ) {}

    public function dispatch(BackOfficeNotification $notification): void
    {
        $recipients = $this->users->activeByRole($notification->type()->audience());
        if ($recipients->isNotEmpty()) {
            $this->notifier->send($recipients, $notification);
        }
    }
}
