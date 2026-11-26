<?php

namespace App\Http\Resources\Public;

use App\Services\Pricing\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Quote $resource
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nights' => count($this->resource->nights),
            'lines' => $this->resource->lines,
            'accommodation' => $this->resource->accommodation,
            'cleaning_fee' => $this->resource->cleaningFee,
            'tourist_tax' => $this->resource->touristTax,
            'total' => $this->resource->total,
            'currency' => 'EUR',
        ];
    }
}
