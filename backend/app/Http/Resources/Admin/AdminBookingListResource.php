<?php

namespace App\Http\Resources\Admin;

use App\Enums\BookingPaymentState;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ligne de la liste des réservations.
 *
 * @mixin Booking
 */
class AdminBookingListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'apartment' => ['id' => $this->apartment_id, 'name' => $this->apartment?->getTranslation('name', 'fr')],
            'guest_name' => $this->guest_name,
            'arrival' => $this->arrival->toDateString(),
            'departure' => $this->departure->toDateString(),
            'nights' => $this->nights(),
            'guests' => $this->adults + $this->children,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'total_amount' => $this->total_amount,
            // Calculé par AdminBookingService::list ; absent ailleurs.
            'payment_state' => ($state = $this->resource->getAttribute('payment_state')) instanceof BookingPaymentState ? $state->value : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
