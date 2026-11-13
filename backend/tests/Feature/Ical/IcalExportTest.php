<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use App\Services\Ical\IcalParser;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Date::setTestNow('2026-11-13 10:00:00');
    config(['booking.frontend_url' => 'https://exemple.fr']);
    $this->apartment = Apartment::factory()->create();
    $this->feed = IcalFeed::factory()->for($this->apartment)->create();
});

function exportOf(IcalFeed $feed): TestResponse
{
    return test()->get("/ical/{$feed->export_token}.ics");
}

/** @return list<array{0: string, 1: string}> nuits publiées [arrivée, départ] */
function exportedStays(TestResponse $response): array
{
    return array_map(
        fn ($event) => [$event->startsOn->toDateString(), $event->endsOn->toDateString()],
        app(IcalParser::class)->parse((string) $response->getContent()),
    );
}

it('publie les réservations directes et les blocages manuels, sans donnée personnelle', function () {
    $booking = directBooking($this->apartment, '2026-11-20', '2026-11-23');
    directBooking($this->apartment, '2026-11-25', '2026-11-27', BookingStatus::Pending);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-12-01', '2026-12-08')->create(['note' => 'Travaux']);

    $response = exportOf($this->feed)->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8')
        ->assertHeader('Cache-Control', 'no-store, private');

    expect(exportedStays($response))->toBe([['2026-11-20', '2026-11-23'], ['2026-11-25', '2026-11-27'], ['2026-12-01', '2026-12-08']]);
    $content = (string) $response->getContent();
    expect($content)->toContain('SUMMARY:Réservé')
        ->not->toContain($booking->guest_name)
        ->not->toContain($booking->guest_email)
        ->not->toContain('Travaux');
});

it('ne renvoie pas à Airbnb ses propres réservations (pas de boucle)', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $this->feed->id, 'external_uid' => 'a@airbnb.com',
    ]);

    expect(exportedStays(exportOf($this->feed)))->toBe([]);
});

it('ne publie ni les séjours terminés, ni les nuits libérées, ni les autres logements', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-01', '2026-11-05')->create();
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['is_active' => false]);
    AvailabilityBlock::factory()->between('2026-11-20', '2026-11-23')->create();
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-10', '2026-11-14')->create(); // en cours

    expect(exportedStays(exportOf($this->feed)))->toBe([['2026-11-10', '2026-11-14']]);
});

it('répond 404 à un token inconnu', function () {
    $this->get('/ical/'.str_repeat('a', 40).'.ics')->assertNotFound();
    $this->get('/ical/court.ics')->assertNotFound();
});

it('donne l’URL d’export au back-office et la révoque sur demande', function () {
    $this->actingAs(User::factory()->create());
    $old = $this->feed->export_token;

    $this->getJson('/api/admin/ical-feeds')
        ->assertJsonPath('data.0.export_url', "https://exemple.fr/ical/$old.ics");

    $response = $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/export-token")->assertOk();

    $new = $this->feed->fresh()->export_token;
    expect($new)->not->toBe($old)->and(strlen($new))->toBe(40);
    $response->assertJsonPath('data.export_url', "https://exemple.fr/ical/$new.ics");
    $this->get("/ical/$old.ics")->assertNotFound();
    $this->get("/ical/$new.ics")->assertOk();
});

it('réserve la révocation aux administrateurs', function () {
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/export-token")->assertForbidden();
});
