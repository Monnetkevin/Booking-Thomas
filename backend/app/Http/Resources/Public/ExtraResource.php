<?php

namespace App\Http\Resources\Public;

use App\Models\Extra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Option proposée au voyageur, dans sa langue. Le prix du séjour est calculé par le devis.
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
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale),
            'description' => $this->getTranslation('description', $locale) ?: null,
            'price' => $this->price,
            'pricing' => $this->pricing->value,
            'max_quantity' => $this->pricing->hasQuantity() ? $this->max_quantity : 1,
        ];
    }
}
