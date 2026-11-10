<?php

namespace App\Http\Resources\Public;

use App\Models\Apartment;
use App\Models\RatePeriod;
use Illuminate\Http\Request;

/**
 * Fiche publique d'un logement. L'adresse exacte n'est jamais publiée : seulement la rue,
 * et une position arrondie à une centaine de mètres.
 *
 * @mixin Apartment
 */
class PublicApartmentDetailResource extends PublicApartmentResource
{
    /**
     * @param  list<array{days_before: int, refund_percent: int}>  $cancellationTiers
     */
    public function __construct(Apartment $apartment, private readonly array $cancellationTiers = [])
    {
        parent::__construct($apartment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description ?: null,
            'check_in_time' => substr((string) $this->check_in_time, 0, 5),
            'check_out_time' => substr((string) $this->check_out_time, 0, 5),
            'max_stay' => $this->max_stay,
            'cleaning_fee' => $this->cleaning_fee,
            'registration_number' => $this->registration_number,
            'cancellation' => [
                'policy' => $this->cancellation_policy->value,
                'tiers' => $this->cancellationTiers,
            ],
            'location' => [
                'street' => self::streetWithoutNumber($this->address),
                'postal_code' => $this->postal_code,
                'city' => $this->city,
                'latitude' => $this->latitude === null ? null : round($this->latitude, 3),
                'longitude' => $this->longitude === null ? null : round($this->longitude, 3),
            ],
            'photos' => PublicPhotoResource::collection($this->photos()),
            'rates' => $this->ratePeriods->map(fn (RatePeriod $period) => [
                'label' => $period->label,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'nightly_price' => $period->nightly_price,
                'min_stay' => $period->min_stay ?? $this->min_stay,
                'weekend_supplement' => $period->weekend_supplement,
            ])->values(),
        ];
    }

    /** « 12 bis, rue de la Paix » → « rue de la Paix ». */
    public static function streetWithoutNumber(string $address): string
    {
        return trim((string) preg_replace('/^\s*\d+\s*(bis|ter|quater|[a-z](?=\s))?\s*,?\s*/iu', '', $address));
    }
}
