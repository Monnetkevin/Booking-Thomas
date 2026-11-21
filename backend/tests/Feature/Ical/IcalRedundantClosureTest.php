<?php

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use App\Services\Ical\IcalCalendarMerger;
use App\Services\Ical\ImportedEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

/**
 * Même date fermée sur le site et sur une plateforme : le bloc importé reste hors contrainte
 * d'exclusion, mais ce n'est pas un conflit (ni alerte, ni planning en double, ni nuits
 * comptées deux fois).
 */
beforeEach(function () {
    Date::setTestNow('2026-11-13 10:00:00');
    Mail::fake();
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create();
    $this->booking = IcalFeed::factory()->for($this->apartment)->create(['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/ical.ics?t=x']);
    $this->airbnb = IcalFeed::factory()->for($this->apartment)->create();
});

/** Le flux contient désormais ces événements : UID → [arrivée, départ, résumé]. */
function feedNow(IcalFeed $feed, array $events): void
{
    app(IcalCalendarMerger::class)->merge($feed, array_map(
        fn (string $uid, array $event) => new ImportedEvent($uid, CarbonImmutable::parse($event[0]), CarbonImmutable::parse($event[1]), $event[2], null),
        array_keys($events),
        $events,
    ));
}

function importedBlock(string $uid): AvailabilityBlock
{
    return AvailabilityBlock::query()->where('external_uid', $uid)->firstOrFail();
}

it('ne signale pas une date fermée sur Booking.com et déjà fermée sur le site', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => '2026-11-20', 'ends_on' => '2026-11-23', 'note' => 'Famille'])->assertCreated();

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
    $this->getJson('/api/admin/dashboard')->assertJsonPath('data.alerts.conflicts', []);
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 0);
    $planning = $this->getJson('/api/admin/planning?from=2026-11-13&days=30')
        ->assertJsonPath('data.figures.open_nights', 27)
        ->assertJsonCount(1, 'data.apartments.0.blocks')
        ->assertJsonPath('data.apartments.0.blocks.0.label', 'Famille');
    expect($planning->json('data.apartments.0.blocks.0.has_conflict'))->toBeFalse();
    Mail::assertNothingQueued();
});

it('fait de même pour une date fermée sur Airbnb, mais garde le conflit d’un séjour Airbnb', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual]);

    feedNow($this->airbnb, [
        'A1' => ['2026-11-20', '2026-11-22', 'Airbnb (Not available)'],
        'A2' => ['2026-11-22', '2026-11-24', 'Reserved'],
    ]);

    expect(importedBlock('A1')->is_redundant_closure)->toBeTrue()
        ->and(importedBlock('A2'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse();
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 1);
});

it('garde le conflit d’une date Booking.com qui recouvre une réservation du site', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    directBooking($this->apartment, '2026-11-22', '2026-11-24');

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-24', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse();
});

it('rend la date Booking.com normale quand la fermeture du site est retirée', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => '2026-11-20', 'ends_on' => '2026-11-23'])->assertCreated();
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);
    $manual = AvailabilityBlock::query()->where('type', BlockType::Manual)->sole();

    $this->deleteJson("/api/admin/blocks/{$manual->id}")->assertSuccessful();

    expect(importedBlock('B1'))->has_conflict->toBeFalse()->is_redundant_closure->toBeFalse();
});

it('ne laisse qu’une fermeture redondante quand la réservation du dessous est annulée', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    $booking = directBooking($this->apartment, '2026-11-22', '2026-11-24');
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-24', 'CLOSED - Not available']]);

    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => false])->assertSuccessful();

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
});
