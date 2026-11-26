<?php

namespace App\Services\Geocoding;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Str;

/**
 * Nominatim (OpenStreetMap) : géocodeur de secours, gratuit et sans clé.
 * Conditions d'usage : un nom d'application dans le User-Agent et au plus une requête
 * par seconde, largement respecté ici (un appel par adresse enregistrée).
 * https://operations.osmfoundation.org/policies/nominatim/
 */
class NominatimGeocoder implements Geocoder
{
    /** Précision minimale : la rue (26) ou mieux ; une ville seule ne place pas un logement. */
    public const MIN_PLACE_RANK = 26;

    public function __construct(
        private readonly Http $http,
        private readonly string $url,
        private readonly string $userAgent,
    ) {}

    public function geocode(string $address, string $postalCode, string $city): ?Coordinates
    {
        try {
            $response = $this->http
                ->timeout(10)
                ->withUserAgent($this->userAgent)
                ->acceptJson()
                ->get($this->url, [
                    'street' => $address,
                    'postalcode' => $postalCode,
                    'city' => $city,
                    'countrycodes' => 'fr',
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 1,
                ]);
        } catch (ConnectionException $e) {
            throw new GeocodingUnavailableException('Nominatim injoignable', previous: $e);
        }

        if ($response->serverError() || in_array($response->status(), [403, 429], true)) {
            throw new GeocodingUnavailableException("Nominatim indisponible ({$response->status()})");
        }

        $place = $response->json('0');
        if (! is_array($place) || ($place['place_rank'] ?? 0) < self::MIN_PLACE_RANK || ! self::inCity($place, $postalCode, $city)) {
            return null;
        }

        return new Coordinates((float) $place['lat'], (float) $place['lon']);
    }

    /**
     * Nominatim ne filtre pas par code postal : « Le Mans » désigne aussi l'arrondissement,
     * et « place de la République, Le Mans » peut tomber dans un village voisin.
     * Le résultat doit donc avoir le bon code postal ou la bonne commune.
     *
     * @param  array<string, mixed>  $place
     */
    private static function inCity(array $place, string $postalCode, string $city): bool
    {
        /** @var array<string, string> $address */
        $address = is_array($place['address'] ?? null) ? $place['address'] : [];
        $normalize = fn (?string $value) => Str::of((string) $value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();

        if (($address['postcode'] ?? null) === $postalCode) {
            return true;
        }

        $placeCity = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;

        return $placeCity !== null && $normalize($placeCity) === $normalize($city);
    }
}
