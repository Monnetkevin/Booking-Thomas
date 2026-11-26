<?php

use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\RatePeriod;
use App\Services\Availability\AvailabilityService;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-10');
    $this->cocon = Apartment::factory()->create(['slug' => 'le-cocon', 'capacity' => 4, 'min_stay' => 2, 'turnover_days' => 0, 'position' => 0]);
    RatePeriod::factory()->for($this->cocon)->create([
        'starts_on' => '2026-11-01', 'ends_on' => '2026-12-31', 'nightly_price' => 8000,
    ]);
});

describe('calendrier d’un logement', function () {
    it('liste les nuits prises et celles sans tarif', function () {
        AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-20', '2026-11-23')->create();

        $response = $this->getJson('/api/apartments/le-cocon/availability?from=2026-11-15&to=2027-01-03')->assertOk();

        expect($response->json('data.unavailable'))->toBe([
            '2026-11-20', '2026-11-21', '2026-11-22', // séjour bloqué ; le 23 (départ) reste libre
            '2027-01-01', '2027-01-02',              // aucune période tarifaire
        ]);
        $response->assertJsonPath('data.first_arrival', '2026-11-11')
            ->assertJsonPath('data.last_arrival', '2027-11-10')
            ->assertJsonPath('data.min_stay', 2)
            ->assertJsonPath('data.periods.0.min_stay', 2);
    });

    it('élargit les nuits prises du battement de ménage', function () {
        $this->cocon->update(['turnover_days' => 1]);
        AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-20', '2026-11-22')->create();

        $this->getJson('/api/apartments/le-cocon/availability?from=2026-11-15&to=2026-11-30')
            ->assertJsonPath('data.unavailable', ['2026-11-19', '2026-11-20', '2026-11-21', '2026-11-22']);
    });

    it('ignore les blocs désactivés et les autres logements', function () {
        AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-20', '2026-11-23')->create(['is_active' => false]);
        AvailabilityBlock::factory()->for(Apartment::factory())->between('2026-11-20', '2026-11-23')->create();

        $this->getJson('/api/apartments/le-cocon/availability?from=2026-11-15&to=2026-11-30')->assertJsonPath('data.unavailable', []);
    });

    it('se met à jour dès que les nuits occupées changent', function () {
        $url = '/api/apartments/le-cocon/availability?from=2026-11-15&to=2026-11-30';
        $this->getJson($url)->assertJsonPath('data.unavailable', []);

        AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-20', '2026-11-21')->create();
        $this->getJson($url)->assertJsonPath('data.unavailable', []); // en cache

        app(AvailabilityService::class)->flushApartment($this->cocon->id);
        $this->getJson($url)->assertJsonPath('data.unavailable', ['2026-11-20']);
    });

    it('répond 404 pour un logement non publié', function () {
        $this->cocon->update(['status' => 'draft']);

        $this->getJson('/api/apartments/le-cocon/availability')->assertNotFound();
    });
});

describe('recherche par dates', function () {
    beforeEach(function () {
        $this->nid = Apartment::factory()->create(['slug' => 'nid-doux', 'capacity' => 2, 'min_stay' => 1, 'position' => 1]);
        RatePeriod::factory()->for($this->nid)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-12-31', 'nightly_price' => 7000]);
    });

    it('met d’abord les logements disponibles, avec le prix du séjour', function () {
        AvailabilityBlock::factory()->for($this->cocon)->between('2026-11-21', '2026-11-22')->create();

        $response = $this->getJson('/api/availability?arrival=2026-11-20&departure=2026-11-23&guests=2')->assertOk();

        $response->assertJsonPath('data.0.slug', 'nid-doux')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('data.0.quote.accommodation', 21000)
            ->assertJsonPath('data.1.slug', 'le-cocon')
            ->assertJsonPath('data.1.available', false)
            ->assertJsonPath('data.1.quote', null)
            ->assertJsonPath('data.1.unavailable_reason', 'Ces dates ne sont pas disponibles.');
    });

    it('tient compte du nombre de voyageurs', function () {
        $this->getJson('/api/availability?arrival=2026-11-20&departure=2026-11-23&guests=3')
            ->assertJsonPath('data.0.slug', 'le-cocon')
            ->assertJsonPath('data.1.available', false);
    });

    it('valide les dates', function () {
        $this->getJson('/api/availability?arrival=2026-11-20&departure=2026-11-19')->assertJsonValidationErrors('departure');
    });
});
