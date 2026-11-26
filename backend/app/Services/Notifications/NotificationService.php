<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Centre de notifications d'un compte : liste, non lues, lecture, préférences. */
class NotificationService
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function list(User $user, bool $unreadOnly = false): LengthAwarePaginator
    {
        return $this->notifications->paginateFor($user, $unreadOnly, self::PER_PAGE);
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCount($user);
    }

    public function markRead(Notification $notification): Notification
    {
        return $this->notifications->markRead($notification);
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllRead($user);
    }

    public function preferences(User $user): NotificationPreferences
    {
        return NotificationPreferences::fromStored($user->role, $user->notification_preferences);
    }

    /**
     * Seuls les types du rôle du compte, et qu'il est permis de couper, sont retenus.
     *
     * @param  array{email?: array<string, bool>, summary_enabled?: bool, summary_hour?: int}  $data
     */
    public function updatePreferences(User $user, array $data): NotificationPreferences
    {
        $preferences = $this->preferences($user)->with(
            $data['email'] ?? [],
            $data['summary_enabled'] ?? null,
            $data['summary_hour'] ?? null,
        );
        $this->users->update($user, ['notification_preferences' => $preferences->toStored()]);

        return $preferences;
    }
}
