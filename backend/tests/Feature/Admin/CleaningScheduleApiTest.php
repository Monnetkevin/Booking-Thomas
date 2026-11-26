<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->cleaner = User::factory()->housekeeping()->create();
    $this->cocon = Apartment::factory()->create([
        'name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon'], 'position' => 1, 'check_in_time' => '16:00:00', 'check_out_time' => '10:30:00',
    ]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'Nest'], 'position' => 2]);
});

it('donne jour par jour départs, arrivées et fermetures, sans donnée personnelle', function () {
    directBooking($this->cocon, '2026-11-17', '2026-11-22');
    directBooking($this->cocon, '2026-11-22', '2026-11-25')->update([
        'guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com', 'adults' => 2, 'children' => 1, 'housekeeping_notes' => 'Lit bébé',
    ]);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-23', '2026-11-26')->create(['type' => BlockType::Manual, 'note' => 'Travaux']);

    $response = $this->actingAs($this->cleaner)->getJson('/api/admin/cleaning-schedule')->assertOk()
        ->assertJsonPath('data.from', '2026-11-20')
        ->assertJsonPath('data.to', '2026-12-03')
        ->assertJsonPath('data.days.0.date', '2026-11-22')
        ->assertJsonPath('data.days.0.movements', [
            ['apartment_id' => $this->cocon->id, 'apartment' => 'Le Cocon', 'kind' => 'departure', 'time' => '10:30', 'guests' => null, 'notes' => null, 'same_day_turnover' => true],
            ['apartment_id' => $this->cocon->id, 'apartment' => 'Le Cocon', 'kind' => 'arrival', 'time' => '16:00', 'guests' => 3, 'notes' => 'Lit bébé', 'same_day_turnover' => true],
        ])
        ->assertJsonPath('data.days.1.date', '2026-11-23')
        ->assertJsonPath('data.days.1.movements.0.kind', 'blocked')
        ->assertJsonPath('data.days.2.date', '2026-11-25')
        ->assertJsonPath('data.days.2.movements.0.kind', 'departure')
        ->assertJsonPath('data.days.2.movements.0.same_day_turnover', false);

    // La fin de la fermeture (26 nov.) ne demande rien au ménage.
    expect($response->json('data.days'))->toHaveCount(3)
        ->and($response->getContent())->not->toContain('Camille')->not->toContain('camille@')->not->toContain('Travaux');
});

it('ignore les réservations en attente de paiement, les fermetures redondantes et les dates hors période', function () {
    directBooking($this->cocon, '2026-11-24', '2026-11-26', BookingStatus::Pending);
    directBooking($this->cocon, '2026-12-10', '2026-12-12');
    $feed = IcalFeed::factory()->for($this->nid)->create();
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-24', '2026-11-26')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'NA', 'note' => 'Airbnb (Not available)', 'is_redundant_closure' => true,
    ]);

    $this->actingAs($this->cleaner)->getJson('/api/admin/cleaning-schedule')->assertOk()->assertJsonPath('data.days', []);
    $this->actingAs($this->cleaner)->getJson('/api/admin/cleaning-schedule?days=30')->assertOk()
        ->assertJsonPath('data.to', '2026-12-19')
        ->assertJsonPath('data.days.0.date', '2026-12-10');
});

it('annonce l’occupation par des proches, puis le ménage à leur départ', function () {
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-21', '2026-11-24')->create(['type' => BlockType::Manual, 'needs_cleaning' => true]);

    $this->actingAs($this->cleaner)->getJson('/api/admin/cleaning-schedule')->assertOk()
        ->assertJsonPath('data.days.0.movements.0.kind', 'occupied')
        ->assertJsonPath('data.days.1.movements.0.kind', 'occupied_end');
});

it('ouvre le planning du ménage à l’administrateur et borne la période', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/admin/cleaning-schedule')->assertOk();
    $this->actingAs($this->cleaner)->getJson('/api/admin/cleaning-schedule?days=20')->assertJsonValidationErrors('days');
});

it('refuse un visiteur', function () {
    $this->getJson('/api/admin/cleaning-schedule')->assertUnauthorized();
});
