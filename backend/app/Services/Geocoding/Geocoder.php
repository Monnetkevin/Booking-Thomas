<?php

namespace App\Services\Geocoding;

interface Geocoder
{
    /**
     * Coordonnées d'une adresse postale française, ou null si elle est introuvable.
     *
     * @throws GeocodingUnavailableException service injoignable : à réessayer plus tard
     */
    public function geocode(string $address, string $postalCode, string $city): ?Coordinates;
}
