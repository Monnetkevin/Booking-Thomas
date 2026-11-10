<?php

use App\Jobs\GeocodeApartment;
use App\Models\Apartment;
use App\Models\User;
use App\Services\Apartment\ApartmentService;
use App\Services\Geocoding\Coordinates;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodingUnavailableException;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('place un nouveau logement sur la carte d’après son adresse', function () {
    $this->geocoder->result = new Coordinates(48.010342, 0.19023);

    $this->postJson('/api/admin/apartments', apartmentPayload([
        'latitude' => 1, 'longitude' => 1,
    ]))
        ->assertCreated();

    $apartment = Apartment::sole();
    expect($this->geocoder->queries)->toBe(['12 rue de la Paix, 72000 Le Mans'])
        ->and($apartment->latitude)->toBe(48.010342)
        ->and($apartment->longitude)->toBe(0.19023);
    $this->getJson("/api/admin/apartments/{$apartment->id}")->assertJsonPath('data.geocoding', 'found');
});

it('signale une adresse introuvable', function () {
    $this->postJson('/api/admin/apartments', apartmentPayload())->assertCreated();

    $this->getJson('/api/admin/apartments/'.Apartment::sole()->id)
        ->assertJsonPath('data.geocoding', 'not_found')
        ->assertJsonPath('data.latitude', null);
});

it('efface puis recalcule la position quand l’adresse change, et seulement alors', function () {
    $apartment = Apartment::factory()->create([
        'address' => '12 rue de la Paix', 'latitude' => 48.01, 'longitude' => 0.19, 'geocoded_at' => now(),
    ]);
    Queue::fake();

    $this->putJson("/api/admin/apartments/{$apartment->id}", apartmentPayload(['capacity' => 3]));
    Queue::assertNothingPushed();

    $this->putJson("/api/admin/apartments/{$apartment->id}", apartmentPayload(['address' => '3 place de la République']))
        ->assertJsonPath('data.geocoding', 'pending')
        ->assertJsonPath('data.latitude', null);
    Queue::assertPushed(GeocodeApartment::class, fn ($job) => $job->apartment->is($apartment));
});

it('réessaie plus tard si le service de géocodage est indisponible', function () {
    $apartment = Apartment::factory()->create(['latitude' => null, 'longitude' => null, 'geocoded_at' => null]);
    $this->app->instance(Geocoder::class, new class implements Geocoder
    {
        public function geocode(string $address, string $postalCode, string $city): ?Coordinates
        {
            throw new GeocodingUnavailableException('Indisponible');
        }
    });

    expect(fn () => app(ApartmentService::class)->refreshCoordinates($apartment))
        ->toThrow(GeocodingUnavailableException::class);
    expect($apartment->fresh()->geocoded_at)->toBeNull();
    expect((new GeocodeApartment($apartment))->tries)->toBeGreaterThan(1);
});

it('programme le calcul pour les logements jamais géocodés', function () {
    Apartment::factory()->create(['geocoded_at' => null]);
    Apartment::factory()->create(['geocoded_at' => now()]);
    Queue::fake();

    $this->artisan('apartments:geocode')->expectsOutputToContain('1 logement(s)')->assertSuccessful();

    Queue::assertPushed(GeocodeApartment::class, 1);
});
