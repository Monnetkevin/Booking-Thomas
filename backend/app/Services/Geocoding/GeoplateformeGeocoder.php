<?php

namespace App\Services\Geocoding;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;

/**
 * API Adresse de la Géoplateforme (IGN, État) : gratuite, sans clé, adresses françaises.
 * https://data.geopf.fr/geocodage/search
 */
class GeoplateformeGeocoder implements Geocoder
{
    /** En dessous, le résultat est trop incertain pour placer le logement sur une carte. */
    public const MIN_SCORE = 0.5;

    public function __construct(
        private readonly Http $http,
        private readonly string $url,
    ) {}

    public function geocode(string $address, string $postalCode, string $city): ?Coordinates
    {
        try {
            $response = $this->http
                ->timeout(5)
                ->retry(2, 500, throw: false)
                ->acceptJson()
                ->get($this->url, [
                    'q' => trim("$address $postalCode $city"),
                    'postcode' => $postalCode,
                    'index' => 'address',
                    'limit' => 1,
                ]);
        } catch (ConnectionException $e) {
            throw new GeocodingUnavailableException('Service de géocodage injoignable', previous: $e);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new GeocodingUnavailableException("Service de géocodage indisponible ({$response->status()})");
        }

        $feature = $response->json('features.0');
        if (! is_array($feature) || ($feature['properties']['score'] ?? 0) < self::MIN_SCORE) {
            return null;
        }

        [$longitude, $latitude] = $feature['geometry']['coordinates'];

        return new Coordinates((float) $latitude, (float) $longitude);
    }
}
