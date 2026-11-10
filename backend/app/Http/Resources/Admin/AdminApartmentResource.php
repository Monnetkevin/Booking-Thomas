<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche complète d'un logement pour le back-office : toutes les traductions,
 * montants en centimes, heures HH:MM.
 *
 * @mixin Apartment
 */
class AdminApartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacity' => $this->capacity,
            'bedrooms' => $this->bedrooms,
            'beds' => $this->beds,
            'bathrooms' => $this->bathrooms,
            'surface_m2' => $this->surface_m2,
            'check_in_time' => substr((string) $this->check_in_time, 0, 5),
            'check_out_time' => substr((string) $this->check_out_time, 0, 5),
            'min_stay' => $this->min_stay,
            'max_stay' => $this->max_stay,
            'turnover_days' => $this->turnover_days,
            'cleaning_fee' => $this->cleaning_fee,
            'cancellation_policy' => $this->cancellation_policy->value,
            'registration_number' => $this->registration_number,
            'position' => $this->position,
            'amenity_ids' => $this->whenLoaded('amenities', fn () => $this->amenities->pluck('id')->values()),
            'photos' => $this->whenLoaded('media', fn () => PhotoResource::collection(
                $this->getMedia(Apartment::PHOTOS),
            )),
            'base_price' => $this->whenLoaded('ratePeriods', fn () => $this->ratePeriods->first()?->nightly_price),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
