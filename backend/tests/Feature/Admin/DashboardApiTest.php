<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\ContactMessage;
use App\Models\IcalFeed;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon'], 'position' => 1]);
    $this->nid = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'Nest'], 'position' => 2]);
});

it('liste les arrivées et départs des 7 prochains jours, sans fermetures ni paiements en cours', function () {
    $booking = directBooking($this->cocon, '2026-11-18', '2026-11-21');
    $booking->update(['guest_name' => 'Camille Martin', 'adults' => 2, 'children' => 1]);
    directBooking($this->cocon, '2026-11-21', '2026-11-30')->update(['guest_name' => 'Léa Dupont']);
    directBooking($this->nid, '2026-11-22', '2026-11-24', BookingStatus::Pending);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-24', '2026-11-26')->create(['type' => BlockType::Manual]);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-26', '2026-11-29')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->nid)->create()->id, 'external_uid' => 'HM1',
    ]);

    $response = $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonCount(7, 'data.movements');

    expect($response->json('data.movements.1'))->toMatchArray([
        'date' => '2026-11-21',
        'arrivals' => [['apartment' => 'Le Cocon', 'label' => 'Léa Dupont', 'reference' => $response->json('data.movements.1.arrivals.0.reference'), 'guests' => 2]],
        'departures' => [['apartment' => 'Le Cocon', 'label' => 'Camille Martin', 'reference' => $booking->reference, 'guests' => 3]],
    ])
        ->and($response->json('data.movements.2.arrivals'))->toBe([])
        ->and($response->json('data.movements.3.arrivals'))->toBe([])
        ->and($response->json('data.movements.6.arrivals.0'))->toMatchArray(['apartment' => 'Le Nid', 'label' => 'Airbnb', 'reference' => null]);
    // Ni la réservation en attente de paiement (22 → 24), ni la fermeture du Nid (24 → 26).
    expect(collect($response->json('data.movements'))->pluck('arrivals')->flatten(1)->pluck('label')->all())->toBe(['Léa Dupont', 'Airbnb']);
});

it('calcule l’occupation et les encaissements du mois', function () {
    // Novembre : 2 logements × 30 nuits = 60 ; 5 fermées → 55 ouvertes ; 9 + 4 vendues.
    $booking = directBooking($this->cocon, '2026-11-21', '2026-11-30');
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-27', '2026-12-03')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->nid)->create()->id, 'external_uid' => 'HM1',
    ]);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-15')->create(['type' => BlockType::Manual]);
    Payment::query()->create(['booking_id' => $booking->id, 'type' => 'payment', 'amount' => 50000, 'status' => PaymentStatus::Succeeded]);
    Payment::query()->create(['booking_id' => $booking->id, 'type' => 'refund', 'amount' => 10000, 'status' => PaymentStatus::Succeeded]);
    Payment::query()->create(['booking_id' => $booking->id, 'type' => 'payment', 'amount' => 99999, 'status' => PaymentStatus::Failed]);
    Payment::query()->forceCreate(['booking_id' => $booking->id, 'type' => 'payment', 'amount' => 70000, 'status' => 'succeeded', 'created_at' => '2026-10-31 23:00:00']);

    $this->getJson('/api/admin/dashboard')
        ->assertJsonPath('data.month.nights', ['direct' => 9, 'platform' => 4, 'closed' => 5])
        ->assertJsonPath('data.month.occupancy', 23.6)
        ->assertJsonPath('data.month.revenue', 40000);
});

it('signale les conflits, les calendriers en échec ou vides refusés et les messages non lus', function () {
    $feed = IcalFeed::factory()->for($this->nid)->create(['consecutive_failures' => 3]);
    IcalFeed::factory()->for($this->cocon)->create(['empty_calendar_refused' => true]);
    IcalFeed::factory()->for(Apartment::factory())->create(['consecutive_failures' => 5, 'is_active' => false]);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-12-01', '2026-12-04')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM1', 'has_conflict' => true,
    ]);
    ContactMessage::query()->create(['name' => 'Léa', 'email' => 'lea@example.com', 'message' => 'Bonjour', 'locale' => 'fr']);

    $this->getJson('/api/admin/dashboard')
        ->assertJsonPath('data.alerts.conflicts', [['apartment' => 'Le Nid', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-04']])
        ->assertJsonCount(2, 'data.alerts.failing_feeds')
        ->assertJsonPath('data.alerts.unread_messages', 1);
});

it('réserve le tableau de bord à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/dashboard')->assertForbidden();
});
