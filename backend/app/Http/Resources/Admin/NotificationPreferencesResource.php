<?php

namespace App\Http\Resources\Admin;

use App\Services\Notifications\NotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Préférences de notification d'un compte : chaque type de son rôle, e-mail activé ou non,
 * réglable ou non, et récapitulatif du ménage.
 *
 * @property NotificationPreferences $resource
 */
class NotificationPreferencesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'types' => $this->resource->types(),
            'summary_enabled' => $this->resource->summaryEnabled,
            'summary_hour' => $this->resource->summaryHour,
        ];
    }
}
