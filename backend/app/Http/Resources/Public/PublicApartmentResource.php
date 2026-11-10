<?php

namespace App\Http\Resources\Public;

use App\Models\Amenity;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Carte d'un logement sur le site public (liste, accueil, recherche). Textes dans la
 * langue de la requête, montants en centimes.
 *
 * @mixin Apartment
 */
class PublicApartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->photos()->first();

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline ?: null,
            'city' => $this->city,
            'capacity' => $this->capacity,
            'bedrooms' => $this->bedrooms,
            'beds' => $this->beds,
            'bathrooms' => $this->bathrooms,
            'surface_m2' => $this->surface_m2,
            'min_stay' => $this->min_stay,
            // Prix par nuit le plus bas des périodes en cours ou à venir.
            'price_from' => $this->ratePeriods->min('nightly_price'),
            'cover' => $cover ? new PublicPhotoResource($cover) : null,
            'amenities' => $this->amenities->map(fn (Amenity $amenity) => [
                'key' => $amenity->key,
                'label' => $amenity->label,
                'icon' => $amenity->icon,
            ])->values(),
        ];
    }

    /**
     * Photos dont les versions publiques sont prêtes, dans l'ordre (la première est la couverture).
     *
     * @return Collection<int, Media>
     */
    protected function photos(): Collection
    {
        return $this->getMedia(Apartment::PHOTOS)
            ->filter(fn (Media $media) => $media->hasGeneratedConversion('thumb'))
            ->values();
    }
}
