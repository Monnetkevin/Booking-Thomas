<?php

namespace App\Models;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notification du back-office, propre à un compte. Contenu figé à l'envoi ; supprimée
 * 90 jours après sa lecture (model:prune).
 *
 * @property string $id
 * @property NotificationType $type
 * @property NotificationLevel $level
 * @property string|null $dedupe_key
 * @property array{title: string, body: string, link: string|null, details?: list<string>} $data
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Notification extends DatabaseNotification
{
    use MassPrunable;

    public const KEEP_READ_DAYS = 90;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'level' => NotificationLevel::class,
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('read_at', '<', now()->subDays(self::KEEP_READ_DAYS));
    }
}
