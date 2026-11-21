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
            'bedrooms' => $this->bedrooms,
            'city' => $this->city,
            'min_stay' => $this->min_stay,
            'position' => $this->position,
            // Prix en lecture (publics sur le site) ; seul l'administrateur les modifie.
            ...$this->prices(),
            'cover_url' => $cover?->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : null,
            'card_url' => $cover?->hasGeneratedConversion('card') ? $cover->getUrl('card') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prices(): array
    {
        $period = $this->ratePeriods->first();

        return [
            'base_price' => $period?->nightly_price,
            'cleaning_fee' => $this->cleaning_fee,
            // Période en cours ou prochaine : tableau des tarifs de base.
            'current_rate' => $period ? [
                'id' => $period->id,
                'label' => $period->label,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'nightly_price' => $period->nightly_price,
                'weekend_supplement' => $period->weekend_supplement,
                'min_stay' => $period->min_stay,
            ] : null,
        ];
    }
}
