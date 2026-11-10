<?php

namespace App\Http\Resources\Public;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photo publiée : versions WebP et AVIF prêtes uniquement, texte alternatif dans la langue.
 *
 * @mixin Media
 */
class PublicPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{fr?: string, en?: string} $alt */
        $alt = $this->getCustomProperty('alt', []);
        $locale = app()->getLocale();

        $sources = [];
        foreach (Apartment::PHOTO_SIZES as $size => [$width]) {
            $sources[$size] = [
                'width' => $width,
                'webp' => $this->hasGeneratedConversion($size) ? $this->getUrl($size) : null,
                'avif' => $this->hasGeneratedConversion("$size-avif") ? $this->getUrl("$size-avif") : null,
            ];
        }

        return [
            'id' => $this->id,
            'alt' => ($alt[$locale] ?? '') ?: ($alt['fr'] ?? ''),
            'width' => $this->getCustomProperty('width'),
            'height' => $this->getCustomProperty('height'),
            'sources' => $sources,
        ];
    }
}
