<?php

namespace App\Services\Catalog;

use App\Models\Amenity;
use App\Models\Apartment;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Données publiques d'un logement, sous forme de tableaux simples dans une langue donnée :
 * ils se mettent en cache sans objet PHP et sont renvoyés tels quels par l'API.
 * L'adresse exacte n'est jamais publiée : seulement la rue et une position arrondie.
 */
class CatalogPresenter
{
    /**
     * Carte d'un logement (liste, accueil, recherche). Montants en centimes.
     *
     * @return array<string, mixed>
     */
    public function summary(Apartment $apartment, string $locale): array
    {
        $cover = $this->readyPhotos($apartment)->first();

        return [
            'slug' => $apartment->slug,
            'name' => $apartment->getTranslation('name', $locale),
            'tagline' => $apartment->getTranslation('tagline', $locale) ?: null,
            'city' => $apartment->city,
            'capacity' => $apartment->capacity,
            'bedrooms' => $apartment->bedrooms,
            'beds' => $apartment->beds,
            'bathrooms' => $apartment->bathrooms,
            'surface_m2' => $apartment->surface_m2,
            'min_stay' => $apartment->min_stay,
            // Prix par nuit le plus bas des périodes en cours ou à venir (relation filtrée).
            'price_from' => $apartment->ratePeriods->min('nightly_price'),
            'cover' => $cover ? $this->photo($cover, $locale) : null,
            'amenities' => $apartment->amenities->map(fn (Amenity $amenity) => [
                'key' => $amenity->key,
                'label' => $amenity->getTranslation('label', $locale),
                'icon' => $amenity->icon,
            ])->values()->all(),
        ];
    }

    /**
     * Fiche complète.
     *
     * @param  list<array{days_before: int, refund_percent: int}>  $cancellationTiers
     * @return array<string, mixed>
     */
    public function detail(Apartment $apartment, string $locale, array $cancellationTiers): array
    {
        return [
            ...$this->summary($apartment, $locale),
            'description' => $apartment->getTranslation('description', $locale) ?: null,
            'check_in_time' => substr((string) $apartment->check_in_time, 0, 5),
            'check_out_time' => substr((string) $apartment->check_out_time, 0, 5),
            'max_stay' => $apartment->max_stay,
            'cleaning_fee' => $apartment->cleaning_fee,
            'registration_number' => $apartment->registration_number,
            'cancellation' => ['policy' => $apartment->cancellation_policy->value, 'tiers' => $cancellationTiers],
            'location' => [
                'street' => self::streetWithoutNumber($apartment->address),
                'postal_code' => $apartment->postal_code,
                'city' => $apartment->city,
                'latitude' => $apartment->latitude === null ? null : round($apartment->latitude, 3),
                'longitude' => $apartment->longitude === null ? null : round($apartment->longitude, 3),
            ],
            'photos' => $this->readyPhotos($apartment)->map(fn (Media $media) => $this->photo($media, $locale))->values()->all(),
        ];
    }

    /**
     * Photo publiée : versions WebP et AVIF prêtes, texte alternatif dans la langue.
     *
     * @return array<string, mixed>
     */
    public function photo(Media $media, string $locale): array
    {
        /** @var array{fr?: string, en?: string} $alt */
        $alt = $media->getCustomProperty('alt', []);

        $originalWidth = (int) $media->getCustomProperty('width', 0);
        $originalHeight = (int) $media->getCustomProperty('height', 0);

        $sources = [];
        foreach (Apartment::PHOTO_SIZES as $size => [$width, $height]) {
            $sources[$size] = [
                'width' => self::generatedWidth($size, $width, $height, $originalWidth, $originalHeight),
                'webp' => $media->hasGeneratedConversion($size) ? $media->getUrl($size) : null,
                'avif' => $media->hasGeneratedConversion("$size-avif") ? $media->getUrl("$size-avif") : null,
            ];
        }

        return [
            'id' => $media->id,
            'alt' => ($alt[$locale] ?? '') ?: ($alt['fr'] ?? ''),
            'width' => $media->getCustomProperty('width'),
            'height' => $media->getCustomProperty('height'),
            'sources' => $sources,
        ];
    }

    /**
     * Largeur réelle d'une version, annoncée dans le srcset : une photo verticale ou petite
     * est moins large que la boîte maximale, et le navigateur doit le savoir pour choisir
     * une version assez nette.
     */
    public static function generatedWidth(string $size, int $maxWidth, int $maxHeight, int $originalWidth, int $originalHeight): int
    {
        if ($size === 'thumb' || $originalWidth <= 0 || $originalHeight <= 0) {
            return $maxWidth;
        }

        $scale = min(1, $maxWidth / $originalWidth, $maxHeight / $originalHeight);

        return (int) floor($originalWidth * $scale);
    }

    /** « 12 bis, rue de la Paix » → « rue de la Paix ». */
    public static function streetWithoutNumber(string $address): string
    {
        return trim((string) preg_replace('/^\s*\d+\s*(bis|ter|quater|[a-z](?=\s))?\s*,?\s*/iu', '', $address));
    }

    /**
     * Photos dont les versions publiques sont prêtes, dans l'ordre (la première est la couverture).
     *
     * @return Collection<int, Media>
     */
    private function readyPhotos(Apartment $apartment): Collection
    {
        return $apartment->getMedia(Apartment::PHOTOS)
            ->filter(fn (Media $media) => $media->hasGeneratedConversion('thumb'))
            ->values();
    }
}
