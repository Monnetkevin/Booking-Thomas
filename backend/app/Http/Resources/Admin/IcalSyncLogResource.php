<?php

namespace App\Http\Resources\Admin;

use App\Models\IcalSyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une synchronisation d'un calendrier, pour le journal du back-office.
 *
 * @mixin IcalSyncLog
 */
class IcalSyncLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'feed_id' => $this->ical_feed_id,
            'trigger' => $this->trigger->value,
            'status' => $this->status->value,
            'created' => $this->created_count,
            'updated' => $this->updated_count,
            'removed' => $this->removed_count,
            'conflicts' => $this->conflict_count,
            'error' => $this->error,
            'duration_ms' => $this->duration_ms,
            'synced_at' => $this->created_at->toIso8601String(),
        ];
    }
}
