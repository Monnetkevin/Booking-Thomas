<?php

namespace App\Http\Resources\Public;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\BookingLinks;
use App\Services\Catalog\CatalogPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Réservation vue par le voyageur depuis son lien signé. L'adresse exacte n'apparaît
 * qu'une fois la réservation confirmée.
 *
 * @mixin Booking
 */
class BookingTrackingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $apartment = $this->apartment;
        $confirmed = in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed], true);
        /** @var array{nights: list<mixed>, lines: list<array<string, mixed>>, accommodation: int, cleaning_fee: int, tourist_tax: int, total: int} $price */
        $price = $this->price_breakdown;
        $presenter = app(CatalogPresenter::class);

        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->status === BookingStatus::Pending ? $this->expires_at?->toIso8601String() : null,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'guest' => ['name' => $this->guest_name, 'email' => $this->guest_email],
            'stay' => [
                'arrival' => $this->arrival->toDateString(),
                'departure' => $this->departure->toDateString(),
                'nights' => $this->nights(),
                'adults' => $this->adults,
                'children' => $this->children,
            ],
            'apartment' => [
                'slug' => $apartment->slug,
                'name' => $apartment->getTranslation('name', $locale),
                'cover' => $presenter->summary($apartment, $locale)['cover'],
                'check_in_time' => substr((string) $apartment->check_in_time, 0, 5),
                'check_out_time' => substr((string) $apartment->check_out_time, 0, 5),
                'address' => $confirmed ? $apartment->address : CatalogPresenter::streetWithoutNumber($apartment->address),
                'postal_code' => $apartment->postal_code,
                'city' => $apartment->city,
            ],
            'price' => [
                'nights' => count($price['nights']),
                'lines' => $price['lines'],
                'accommodation' => $price['accommodation'],
                'cleaning_fee' => $price['cleaning_fee'],
                'tourist_tax' => $price['tourist_tax'],
                'total' => $this->total_amount,
                'currency' => 'EUR',
            ],
            'cancellation' => $this->cancellation_policy,
            'pdf_url' => $confirmed ? app(BookingLinks::class)->pdfUrl($this->resource) : null,
        ];
    }
}
