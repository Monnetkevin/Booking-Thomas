<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photo d'un logement. Les versions sont générées en tâche de fond : une URL vaut null
 * tant que sa conversion n'est pas prête.
 *
 * @mixin Media
 */
class PhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $urls = [];
        foreach (array_keys(Apartment::PHOTO_SIZES) as $size) {
            foreach ([$size => 'webp', "$size-avif" => 'avif'] as $conversion => $format) {
                $urls[$size][$format] = $this->hasGeneratedConversion($conversion) ? $this->getUrl($conversion) : null;
            }
        }

        return [
            'id' => $this->id,
            'position' => $this->order_column,
            'alt' => $this->getCustomProperty('alt', ['fr' => '', 'en' => '']),
            'width' => $this->getCustomProperty('width'),
            'height' => $this->getCustomProperty('height'),
            'ready' => $this->hasGeneratedConversion('thumb'),
            'urls' => $urls,
        ];
    }
}
