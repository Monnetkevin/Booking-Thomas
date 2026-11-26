<?php

namespace Tests\Fakes;

use App\Services\Geocoding\Coordinates;
use App\Services\Geocoding\Geocoder;

/** Géocodeur des tests : aucune requête réseau, résultat choisi par le test. */
class FakeGeocoder implements Geocoder
{
    public ?Coordinates $result = null;

    /** @var list<string> */
    public array $queries = [];

    public function geocode(string $address, string $postalCode, string $city): ?Coordinates
    {
        $this->queries[] = "$address, $postalCode $city";

        return $this->result;
    }
}
