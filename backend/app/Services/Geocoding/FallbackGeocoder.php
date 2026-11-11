<?php

namespace App\Services\Geocoding;

use Illuminate\Support\Facades\Log;

/**
 * Interroge les géocodeurs dans l'ordre : le suivant ne sert que si le précédent est
 * injoignable. Seul le service principal peut déclarer une adresse introuvable : si le
 * secours ne la trouve pas, on considère le géocodage comme indisponible, pour que la
 * tâche réessaie plus tard auprès du service principal.
 */
class FallbackGeocoder implements Geocoder
{
    /** @var list<Geocoder> */
    private readonly array $geocoders;

    public function __construct(Geocoder ...$geocoders)
    {
        $this->geocoders = array_values($geocoders);
    }

    public function geocode(string $address, string $postalCode, string $city): ?Coordinates
    {
        $lastError = null;

        foreach ($this->geocoders as $index => $geocoder) {
            try {
                $coordinates = $geocoder->geocode($address, $postalCode, $city);
                if ($coordinates !== null || $index === 0) {
                    return $coordinates;
                }
                $lastError = new GeocodingUnavailableException('Adresse introuvable par le géocodeur de secours '.$geocoder::class);
            } catch (GeocodingUnavailableException $e) {
                Log::warning('Géocodeur indisponible, passage au suivant', ['geocoder' => $geocoder::class, 'error' => $e->getMessage()]);
                $lastError = $e;
            }
        }

        throw $lastError ?? new GeocodingUnavailableException('Aucun géocodeur configuré');
    }
}
