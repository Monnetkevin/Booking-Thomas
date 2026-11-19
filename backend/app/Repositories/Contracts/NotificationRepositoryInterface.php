<?php

namespace App\Repositories\Contracts;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    /**
     * Enregistre une notification du compte ; une notification non lue du même type et de
     * la même clé d'objet est rafraîchie (contenu, niveau, date) au lieu d'être répétée.
     *
     * @param  array{id: string, type: NotificationType, level: NotificationLevel, dedupe_key: string|null, data: array<string, mixed>}  $attributes
     */
    public function record(User $user, array $attributes): Notification;

    /**
     * Notifications du compte, les plus récentes d'abord.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginateFor(User $user, bool $unreadOnly, int $perPage): LengthAwarePaginator;

    public function unreadCount(User $user): int;

    public function markRead(Notification $notification): Notification;

    /** Marque lues toutes les notifications du compte ; renvoie leur nombre. */
    public function markAllRead(User $user): int;
}
