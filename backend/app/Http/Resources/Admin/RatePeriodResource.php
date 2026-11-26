<?php

namespace App\Http\Resources\Admin;

use App\Models\RatePeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RatePeriod
 */
class RatePeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apartment_id' => $this->apartment_id,
            'label' => $this->label,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'nightly_price' => $this->nightly_price,
            'min_stay' => $this->min_stay,
            'weekend_supplement' => $this->weekend_supplement,
            'arrival_days' => $this->arrival_days,
        ];
    }
}
