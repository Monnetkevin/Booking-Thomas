<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ligne de la liste des logements du back-office.
 *
 * @mixin Apartment
 */
class AdminApartmentListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->getFirstMedia(Apartment::PHOTOS);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslation('name', 'fr'),
            'status' => $this->status->value,
            'capacity' => $this->capacity,
            'position' => $this->position,
            // Prix de la période en cours ou de la prochaine, en centimes (relation filtrée).
            'base_price' => $this->ratePeriods->first()?->nightly_price,
            'cover_url' => $cover?->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : null,
        ];
    }
}
