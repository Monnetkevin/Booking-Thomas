<?php

namespace App\Http\Resources\Admin;

use App\Models\AvailabilityBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AvailabilityBlock
 */
class BlockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apartment_id' => $this->apartment_id,
            'type' => $this->type->value,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'note' => $this->note,
        ];
    }
}
