<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Code promo ou offre, avec ses utilisations et le montant remisé.
 *
 * @mixin Coupon
 */
class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'target' => $this->target->value,
            'type' => $this->type->value,
            'value' => $this->value,
            'extra' => $this->extra ? ['id' => $this->extra->id, 'name' => $this->extra->getTranslation('name', 'fr')] : null,
            'free_quantity' => $this->free_quantity,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'max_uses' => $this->max_uses,
            'max_uses_per_email' => $this->max_uses_per_email,
            'min_nights' => $this->min_nights,
            'min_amount' => $this->min_amount,
            'automatic' => $this->automatic,
            'is_active' => $this->is_active,
            'apartments' => $this->apartments->map(fn (Apartment $apartment) => ['id' => $apartment->id, 'name' => $apartment->getTranslation('name', 'fr')])->values()->all(),
            'uses' => (int) ($this->uses_count ?? 0),
            'discount_total' => (int) ($this->discount_total ?? 0),
        ];
    }
}
