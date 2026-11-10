<?php

use App\Services\Geocoding\GeocodingUnavailableException;
use App\Services\Geocoding\GeoplateformeGeocoder;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const GEOCODING_URL = 'https://data.geopf.fr/geocodage/search';

function geocoder(): GeoplateformeGeocoder
{
    return new GeoplateformeGeocoder(app(Factory::class), GEOCODING_URL);
}

function feature(float $score): array
{
    return ['features' => [[
        'geometry' => ['type' => 'Point', 'coordinates' => [0.19023, 48.010342]],
        'properties' => ['label' => '12 Rue de la Paix 72000 Le Mans', 'score' => $score],
    ]]];
}

it('interroge l’API Adresse avec l’adresse complète et lit les coordonnées', function () {
    Http::fake([GEOCODING_URL.'*' => Http::response(feature(0.97))]);

    $coordinates = geocoder()->geocode('12 rue de la Paix', '72000', 'Le Mans');

    expect($coordinates->latitude)->toBe(48.010342)->and($coordinates->longitude)->toBe(0.19023);
    Http::assertSent(fn (Request $request) => $request['q'] === '12 rue de la Paix 72000 Le Mans'
        && $request['postcode'] === '72000'
        && $request['index'] === 'address');
});

it('ignore un résultat trop incertain ou absent', function () {
    Http::fake([GEOCODING_URL.'*' => Http::sequence()->push(feature(0.3))->push(['features' => []])]);

    expect(geocoder()->geocode('nulle part', '72000', 'Le Mans'))->toBeNull()
        ->and(geocoder()->geocode('nulle part', '72000', 'Le Mans'))->toBeNull();
});

it('signale un service indisponible pour que la tâche soit réessayée', function () {
    Http::fake([GEOCODING_URL.'*' => Http::response('', 503)]);

    geocoder()->geocode('12 rue de la Paix', '72000', 'Le Mans');
})->throws(GeocodingUnavailableException::class);
