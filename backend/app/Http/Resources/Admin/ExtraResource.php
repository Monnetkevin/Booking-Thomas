<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use App\Models\Extra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Option du catalogue, avec ses textes dans les deux langues et ses logements.
 *
 * @mixin Extra
 */
class ExtraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_fr' => $this->getTranslation('name', 'fr', false),
            'name_en' => $this->getTranslation('name', 'en', false) ?: null,
            'description_fr' => $this->getTranslation('description', 'fr', false) ?: null,
            'description_en' => $this->getTranslation('description', 'en', false) ?: null,
            'price' => $this->price,
            'pricing' => $this->pricing->value,
            'max_quantity' => $this->max_quantity,
            'lead_time_hours' => $this->lead_time_hours,
            'is_active' => $this->is_active,
            'apartments' => $this->apartments->map(fn (Apartment $apartment) => ['id' => $apartment->id, 'name' => $apartment->getTranslation('name', 'fr')])->values()->all(),
        ];
    }
}
