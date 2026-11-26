<?php

use App\Services\Geocoding\FallbackGeocoder;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodingUnavailableException;
use App\Services\Geocoding\GeoplateformeGeocoder;
use App\Services\Geocoding\NominatimGeocoder;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const IGN_URL = 'https://data.geopf.fr/geocodage/search';
const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

function nominatim(): NominatimGeocoder
{
    return new NominatimGeocoder(app(Factory::class), NOMINATIM_URL, 'BookingThomas/1.0');
}

function chain(): FallbackGeocoder
{
    return new FallbackGeocoder(new GeoplateformeGeocoder(app(Factory::class), IGN_URL), nominatim());
}

function nominatimPlace(int $rank, string $postcode = '72000', string $city = 'Le Mans'): array
{
    return [['lat' => '48.0086870', 'lon' => '0.2001781', 'place_rank' => $rank, 'address' => ['postcode' => $postcode, 'city' => $city]]];
}

it('interroge Nominatim par champs, en France, avec un nom d’application', function () {
    Http::fake([NOMINATIM_URL.'*' => Http::response(nominatimPlace(26))]);

    $coordinates = nominatim()->geocode('17 avenue de Paderborn', '72000', 'Le Mans');

    expect($coordinates->latitude)->toBe(48.008687)->and($coordinates->longitude)->toBe(0.2001781);
    Http::assertSent(fn (Request $request) => $request['street'] === '17 avenue de Paderborn'
        && $request['postalcode'] === '72000'
        && $request['countrycodes'] === 'fr'
        && $request->hasHeader('User-Agent', 'BookingThomas/1.0'));
});

it('refuse un résultat Nominatim moins précis que la rue', function () {
    Http::fake([NOMINATIM_URL.'*' => Http::sequence()->push(nominatimPlace(16))->push([])]);

    expect(nominatim()->geocode('nulle part', '72000', 'Le Mans'))->toBeNull()
        ->and(nominatim()->geocode('nulle part', '72000', 'Le Mans'))->toBeNull();
});

it('refuse un résultat Nominatim hors de la commune (code postal ignoré par Nominatim)', function () {
    // Cas réel : « 3 place de la République, Le Mans » renvoyait Ballon-Saint Mars (72290).
    Http::fake([NOMINATIM_URL.'*' => Http::response([[
        'lat' => '48.1750795', 'lon' => '0.2343668', 'place_rank' => 30,
        'address' => ['postcode' => '72290', 'village' => 'Ballon-Saint Mars'],
    ]])]);

    expect(nominatim()->geocode('3 place de la République', '72000', 'Le Mans'))->toBeNull();
});

it('accepte la bonne commune même sans code postal, accents et casse ignorés', function () {
    Http::fake([NOMINATIM_URL.'*' => Http::response([[
        'lat' => '48.0', 'lon' => '0.2', 'place_rank' => 30, 'address' => ['town' => 'LE MANS'],
    ]])]);

    expect(nominatim()->geocode('1 rue Test', '72000', 'Le Mans'))->not->toBeNull();
});

it('réessaiera plus tard si l’IGN est en panne et que le secours ne trouve pas l’adresse', function () {
    Http::fake([
        IGN_URL.'*' => Http::response('', 504),
        NOMINATIM_URL.'*' => Http::response([]),
    ]);

    chain()->geocode('3 place de la République', '72000', 'Le Mans');
})->throws(GeocodingUnavailableException::class);

it('passe à Nominatim quand l’IGN est en panne', function () {
    Http::fake([
        IGN_URL.'*' => Http::response(['message' => 'The upstream server is timing out'], 504),
        NOMINATIM_URL.'*' => Http::response(nominatimPlace(30)),
    ]);

    expect(chain()->geocode('17 avenue de Paderborn', '72000', 'Le Mans')?->latitude)->toBe(48.008687);
});

it('n’appelle pas le secours quand l’IGN répond, même sans résultat', function () {
    Http::fake([
        IGN_URL.'*' => Http::response(['features' => []]),
        NOMINATIM_URL.'*' => Http::response(nominatimPlace(30)),
    ]);

    expect(chain()->geocode('nulle part', '72000', 'Le Mans'))->toBeNull();
    Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), NOMINATIM_URL));
});

it('signale l’indisponibilité quand les deux services sont en panne', function () {
    Http::fake(['*' => Http::response('', 503)]);

    chain()->geocode('17 avenue de Paderborn', '72000', 'Le Mans');
})->throws(GeocodingUnavailableException::class);

it('utilise la chaîne IGN puis Nominatim dans l’application', function () {
    app()->forgetInstance(Geocoder::class); // retire le faux géocodeur installé par TestCase

    expect(app(Geocoder::class))->toBeInstanceOf(FallbackGeocoder::class);
});
