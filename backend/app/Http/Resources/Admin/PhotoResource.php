<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Media
 */
class PhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->order_column,
            'alt' => $this->getCustomProperty('alt', ['fr' => '', 'en' => '']),
            'width' => $this->getCustomProperty('width'),
            'height' => $this->getCustomProperty('height'),
            // Les conversions sont faites en tâche de fond : null tant qu'elles ne sont pas prêtes.
            'thumb_url' => $this->hasGeneratedConversion('thumb') ? $this->getUrl('thumb') : null,
            'ready' => $this->hasGeneratedConversion('thumb'),
        ];
    }
}
