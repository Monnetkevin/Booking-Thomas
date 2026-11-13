<?php

namespace App\Http\Resources\Public;

use App\Models\Booking;
use App\Services\Booking\BookingLinks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Réservation tout juste créée : de quoi enchaîner sur le paiement (jalon 9).
 *
 * @mixin Booking
 */
class PendingBookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'total' => $this->total_amount,
            'currency' => 'EUR',
            // Page de suivi du voyageur (lien signé, aussi envoyé par e-mail).
            'tracking_url' => app(BookingLinks::class)->trackingUrl($this->resource),
        ];
    }
}
