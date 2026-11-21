<?php

namespace App\Http\Resources\Admin;

use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Résultats de la recherche rapide : juste ce qu'il faut pour les afficher et ouvrir la fiche.
 *
 * @property array{bookings: Collection<int, Booking>, apartments: Collection<int, Apartment>} $resource
 */
class SearchResultsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bookings' => $this->resource['bookings']->map(fn (Booking $booking) => [
                'reference' => $booking->reference,
                'guest_name' => $booking->guest_name,
                'apartment' => $booking->apartment?->getTranslation('name', 'fr'),
                'arrival' => $booking->arrival->toDateString(),
                'departure' => $booking->departure->toDateString(),
                'status' => $booking->status->value,
            ])->all(),
            'apartments' => $this->resource['apartments']->map(fn (Apartment $apartment) => [
                'id' => $apartment->id,
                'name' => $apartment->getTranslation('name', 'fr'),
                'status' => $apartment->status->value,
            ])->all(),
        ];
    }
}
