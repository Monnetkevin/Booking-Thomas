<?php

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\PlatformPayout;
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
    // Planning : la fermeture Booking.com reste visible, en retrait, sans conflit ; 3 nuits fermées comptées une fois.
    $this->getJson('/api/admin/planning?from=2026-11-13&days=30')
        ->assertJsonPath('data.figures.open_nights', 27)
        ->assertJsonPath('data.figures.sold_nights', 0)
        ->assertJsonPath('data.figures.arrivals', 0)
        ->assertJsonPath('data.apartments.0.blocks.0.label', 'Famille')
        ->assertJsonPath('data.apartments.0.blocks.0.has_conflict', false)
        ->assertJsonPath('data.apartments.0.blocks.0.redundant_closure', false)
        ->assertJsonPath('data.apartments.0.blocks.1.label', 'Booking.com')
        ->assertJsonPath('data.apartments.0.blocks.1.has_conflict', false)
        ->assertJsonPath('data.apartments.0.blocks.1.redundant_closure', true);
    Mail::assertNothingQueued();
});

it('garde le conflit d’une fermeture importée qui dépasse la fermeture du site', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-21')->create(['type' => BlockType::Manual]);

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-27', 'CLOSED - Not available']]);
    feedNow($this->airbnb, ['A1' => ['2026-11-28', '2026-12-01', 'Airbnb (Not available)']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse()
        ->and(importedBlock('A1'))->has_conflict->toBeFalse();
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 1);
    $this->getJson('/api/admin/planning?from=2026-11-13&days=30')->assertJsonPath('data.apartments.0.blocks.1.has_conflict', true);
});

it('couvre une fermeture importée par plusieurs fermetures du site bout à bout', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-22', '2026-11-25')->create(['type' => BlockType::Manual]);

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-25', 'CLOSED - Not available']]);

    expect(importedBlock('B1')->is_redundant_closure)->toBeTrue();
});

it('refait un vrai conflit d’une date Booking.com dont un versement prouve que c’était un séjour', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 0);

    // Versement importé plus tard : la date fermée était une réservation Booking.com.
    PlatformPayout::query()->create([
        'platform' => 'booking', 'confirmation_code' => 'BK1', 'entry_key' => 'BK1', 'apartment_id' => $this->apartment->id,
        'arrival' => '2026-11-20', 'departure' => '2026-11-23', 'nights' => 3, 'net_amount' => 30000,
    ]);

    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 1);
    $this->getJson('/api/admin/dashboard')->assertJsonPath('data.alerts.conflicts.0.starts_on', '2026-11-20');
    $this->getJson('/api/admin/planning?from=2026-11-13&days=30')
        ->assertJsonPath('data.apartments.0.blocks.1.has_conflict', true)
        ->assertJsonPath('data.apartments.0.blocks.1.redundant_closure', false);
});

it('montre au tableau de bord une arrivée Booking.com sans versement importé, pas une fermeture redondante', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-16', '2026-11-18')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, [
        'B1' => ['2026-11-15', '2026-11-16', 'CLOSED - Not available'],
        'B2' => ['2026-11-16', '2026-11-18', 'CLOSED - Not available'],
    ]);

    $movements = collect($this->getJson('/api/admin/dashboard')->json('data.movements'))->keyBy('date');

    expect(array_column($movements['2026-11-15']['arrivals'], 'label'))->toBe(['Booking.com'])
        ->and($movements['2026-11-16']['arrivals'])->toBe([]);
});

it('ne marque pas redondante une date Booking.com déjà confirmée par un versement au moment de l’import', function () {
    PlatformPayout::query()->create([
        'platform' => 'booking', 'confirmation_code' => 'BK1', 'entry_key' => 'BK1', 'apartment_id' => $this->apartment->id,
        'arrival' => '2026-11-20', 'departure' => '2026-11-23', 'nights' => 3, 'net_amount' => 30000,
    ]);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual]);

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse();
});

it('remet à zéro l’indicateur d’une fermeture redondante annulée sur la plateforme', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available'], 'B2' => ['2026-12-10', '2026-12-12', 'CLOSED - Not available']]);

    feedNow($this->booking, ['B2' => ['2026-12-10', '2026-12-12', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->is_active->toBeFalse()->has_conflict->toBeFalse()->is_redundant_closure->toBeFalse();
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

it('garde le conflit quand la réservation du dessous est annulée mais que la fermeture du site ne couvre pas tout', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    $booking = directBooking($this->apartment, '2026-11-22', '2026-11-24');
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-24', 'CLOSED - Not available']]);

    $this->postJson("/api/admin/bookings/{$booking->reference}/cancel", ['notify_guest' => false])->assertSuccessful();

    // Les 22 et 23 ne sont tenus que par la date Booking.com : c'est peut-être un séjour.
    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse();
});

it('signale une seule fois un séjour Airbnb posé sur une fermeture du site, aussi fermée sur Booking.com', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-24')->create(['type' => BlockType::Manual]);
    feedNow($this->airbnb, ['A1' => ['2026-11-20', '2026-11-24', 'Reserved']]);
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-24', 'CLOSED - Not available']]);

    expect(importedBlock('A1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse()
        ->and(importedBlock('B1')->is_redundant_closure)->toBeTrue();
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 1);

    // Le séjour Airbnb est annulé : la date Booking.com reste une fermeture redondante.
    app(IcalCalendarMerger::class)->merge($this->airbnb, [], acceptEmpty: true);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 0);
});
