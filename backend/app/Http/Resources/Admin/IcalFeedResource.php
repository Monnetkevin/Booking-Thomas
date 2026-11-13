<?php

namespace App\Http\Resources\Admin;

use App\Models\IcalFeed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Calendrier d'une plateforme pour un logement, état de sa dernière synchronisation.
 *
 * @mixin IcalFeed
 */
class IcalFeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apartment_id' => $this->apartment_id,
            'provider' => $this->provider->value,
            'provider_label' => $this->provider->label(),
            'import_url' => $this->import_url,
            'is_active' => $this->is_active,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'last_status' => $this->last_status?->value,
            'last_error' => $this->last_error,
            'consecutive_failures' => $this->consecutive_failures,
        ];
    }
}
