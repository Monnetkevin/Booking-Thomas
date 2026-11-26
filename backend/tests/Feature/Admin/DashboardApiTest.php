<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\ContactMessage;
use App\Models\IcalFeed;
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

it('calcule l’occupation, les revenus directs et les séjours de la période, comparés à la précédente', function () {
    // Novembre : 2 logements × 30 nuits = 60 ; 5 fermées → 55 ouvertes ; 9 + 4 vendues.
    $booking = directBooking($this->cocon, '2026-11-21', '2026-11-30');
    $booking->update(['total_amount' => 50000, 'tourist_tax' => 2000]);
    recordPayment($booking, 50000);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-27', '2026-12-03')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => IcalFeed::factory()->for($this->nid)->create()->id, 'external_uid' => 'HM1',
    ]);
    AvailabilityBlock::factory()->for($this->nid)->between('2026-11-10', '2026-11-15')->create(['type' => BlockType::Manual]);
    // Octobre : un séjour direct de 30 000 (taxe 1 000).
    $october = directBooking($this->cocon, '2026-10-05', '2026-10-08');
    $october->update(['total_amount' => 30000, 'tourist_tax' => 1000]);
    recordPayment($october, 30000);
    // Réservation non payée : ni revenu ni séjour.
    directBooking($this->nid, '2026-11-02', '2026-11-04', BookingStatus::Pending);

    $this->getJson('/api/admin/dashboard')
        ->assertJsonPath('data.period', ['key' => 'month', 'from' => '2026-11-01', 'to' => '2026-12-01'])
        ->assertJsonPath('data.figures.occupancy.value', 23.6)
        ->assertJsonPath('data.figures.revenue', ['value' => 48000, 'previous' => 29000])
        ->assertJsonPath('data.figures.stays', ['value' => 2, 'direct' => 1, 'previous' => 1])
        ->assertJsonCount(6, 'data.revenue_months')
        ->assertJsonPath('data.revenue_months.5', ['month' => '2026-11', 'revenue' => 48000])
        ->assertJsonPath('data.revenue_months.4', ['month' => '2026-10', 'revenue' => 29000]);

    // Semaine du lundi 16 au dimanche 22 novembre : une arrivée directe le 21.
    $this->getJson('/api/admin/dashboard?period=week')
        ->assertJsonPath('data.period', ['key' => 'week', 'from' => '2026-11-16', 'to' => '2026-11-23'])
        ->assertJsonPath('data.figures.stays.value', 1);
    $this->getJson('/api/admin/dashboard?period=year')->assertJsonPath('data.period.from', '2026-01-01');
    $this->getJson('/api/admin/dashboard?period=decade')->assertJsonValidationErrors('period');
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

it('n’alerte pas pour un logement supprimé', function () {
    $gone = Apartment::factory()->create();
    $feed = IcalFeed::factory()->for($gone)->create(['consecutive_failures' => 5]);
    AvailabilityBlock::factory()->for($gone)->between('2026-12-01', '2026-12-04')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM2', 'has_conflict' => true,
    ]);
    $gone->delete();

    $this->getJson('/api/admin/dashboard')
        ->assertJsonPath('data.alerts.conflicts', [])
        ->assertJsonPath('data.alerts.failing_feeds', []);
});

it('réserve le tableau de bord à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/dashboard')->assertForbidden();
});

it('donne les compteurs de la barre : conflits, calendriers en échec, messages, dernière lecture', function () {
    IcalFeed::factory()->for($this->nid)->create(['consecutive_failures' => 3, 'last_succeeded_at' => '2026-11-20 08:00:00']);
    $feed = IcalFeed::factory()->for($this->cocon)->create(['last_succeeded_at' => '2026-11-20 09:30:00']);
    AvailabilityBlock::factory()->for($this->cocon)->between('2026-12-01', '2026-12-04')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $feed->id, 'external_uid' => 'HM9', 'has_conflict' => true,
    ]);
    ContactMessage::query()->create(['name' => 'Léa', 'email' => 'lea@example.com', 'message' => 'Bonjour', 'locale' => 'fr']);

    $this->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.conflicts', 1)
        ->assertJsonPath('data.failing_feeds', 1)
        ->assertJsonPath('data.unread_messages', 1)
        ->assertJsonPath('data.last_sync_at', fn (string $value) => str_starts_with($value, '2026-11-20T09:30:00'));

    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/overview')->assertForbidden();
});
