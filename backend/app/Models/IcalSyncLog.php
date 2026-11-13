<?php

namespace App\Models;

use App\Enums\IcalSyncStatus;
use App\Enums\IcalSyncTrigger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une lecture d'un calendrier : issue, nuits ajoutées, déplacées, libérées, conflits.
 * Supprimée au bout de 30 jours (model:prune).
 *
 * @property int $id
 * @property int $ical_feed_id
 * @property IcalSyncTrigger $trigger
 * @property IcalSyncStatus $status
 * @property int $created_count
 * @property int $updated_count
 * @property int $removed_count
 * @property int $conflict_count
 * @property string|null $error
 * @property int $duration_ms
 * @property Carbon $created_at
 */
#[Fillable([
    'ical_feed_id', 'trigger', 'status', 'created_count', 'updated_count', 'removed_count',
    'conflict_count', 'error', 'duration_ms',
])]
class IcalSyncLog extends Model
{
    use Prunable;

    public const KEEP_DAYS = 30;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => IcalSyncTrigger::class,
            'status' => IcalSyncStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    /** @return BelongsTo<IcalFeed, $this> */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(IcalFeed::class, 'ical_feed_id');
    }
}
