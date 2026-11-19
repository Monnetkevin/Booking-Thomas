<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function record(User $user, array $attributes): Notification
    {
        $existing = $attributes['dedupe_key'] === null ? null : $this->of($user)
            ->whereNull('read_at')
            ->where('type', $attributes['type']->value)
            ->where('dedupe_key', $attributes['dedupe_key'])
            ->first();

        if ($existing !== null) {
            // Remonte en tête de liste, avec le contenu le plus récent.
            $existing->forceFill([
                'level' => $attributes['level'],
                'data' => $attributes['data'],
                'created_at' => now(),
            ])->save();

            return $existing;
        }

        /** @var Notification */
        return $user->notifications()->create([
            'id' => $attributes['id'],
            'type' => $attributes['type'],
            'level' => $attributes['level'],
            'dedupe_key' => $attributes['dedupe_key'],
            'data' => $attributes['data'],
        ]);
    }

    public function paginateFor(User $user, bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        return $this->of($user)
            ->when($unreadOnly, fn (Builder $query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $this->of($user)->whereNull('read_at')->count();
    }

    public function markRead(Notification $notification): Notification
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return $this->of($user)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
    }

    /**
     * @return Builder<Notification>
     */
    private function of(User $user): Builder
    {
        return Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey());
    }
}
