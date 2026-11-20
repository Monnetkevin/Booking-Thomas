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
    $this->actingAs(User::factory()->create());
});

it('donne, par logement dans l’ordre du site, les nuits occupées de la période', function () {
    $second = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'Nest'], 'position' => 2]);
    $first = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon'], 'position' => 1]);
    $booking = directBooking($first, '2026-11-18', '2026-11-22');
    $booking->update(['guest_name' => 'Camille Martin', 'adults' => 2, 'children' => 1]);
    directBooking($first, '2026-11-25', '2026-11-27', BookingStatus::Pending);
    AvailabilityBlock::factory()->for($second)->between('2026-11-28', '2026-12-02')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($second)->create()->id,
        'external_uid' => 'HM1', 'external_url' => 'https://www.airbnb.com/hosting/reservations/details/HM1', 'has_conflict' => true,
    ]);
    AvailabilityBlock::factory()->for($second)->between('2026-11-21', '2026-11-23')->create(['type' => BlockType::Manual, 'note' => 'Travaux']);
    AvailabilityBlock::factory()->for($second)->between('2026-12-10', '2026-12-12')->create(['type' => BlockType::Manual]);
    AvailabilityBlock::factory()->for($second)->between('2026-11-24', '2026-11-26')->create(['type' => BlockType::Manual, 'is_active' => false]);

    $response = $this->getJson('/api/admin/planning?from=2026-11-20&days=14')->assertOk()
        ->assertJsonPath('data.from', '2026-11-20')
        ->assertJsonPath('data.to', '2026-12-04')
        ->assertJsonPath('data.apartments.0.name', 'Le Cocon')
        ->assertJsonPath('data.apartments.0.blocks.0.label', 'Camille Martin')
        ->assertJsonPath('data.apartments.0.blocks.0.booking.reference', $booking->reference)
        ->assertJsonPath('data.apartments.0.blocks.0.booking.guests', 3)
        ->assertJsonPath('data.apartments.0.blocks.1.booking.status', 'pending')
        ->assertJsonPath('data.apartments.1.blocks.0.label', 'Travaux')
        ->assertJsonPath('data.apartments.1.blocks.1.label', 'Airbnb')
        ->assertJsonPath('data.apartments.1.blocks.1.has_conflict', true)
        // 2 logements × 14 nuits : 2 vendues en direct, 4 sur Airbnb, 2 fermées, 2 en paiement.
        ->assertJsonPath('data.figures', [
            'sold_nights' => 6, 'open_nights' => 26, 'occupancy' => 23.1, 'arrivals' => 1,
            'direct_arrivals' => 0, 'free_nights' => 18, 'free_weekend_nights' => 4,
        ]);

    expect($response->json('data.apartments.1.blocks'))->toHaveCount(2);
});

it('prend aujourd’hui et 14 jours par défaut, et borne la période', function () {
    $this->getJson('/api/admin/planning')->assertJsonPath('data.from', '2026-11-20')->assertJsonPath('data.to', '2026-12-04');
    $this->getJson('/api/admin/planning?days=90')->assertJsonValidationErrors('days');
    $this->getJson('/api/admin/planning?from=demain')->assertJsonValidationErrors('from');
});

it('réserve le planning (noms des voyageurs) à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/planning')->assertForbidden();
});
