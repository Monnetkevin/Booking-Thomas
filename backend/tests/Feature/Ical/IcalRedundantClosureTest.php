<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\StayChangeKind;
use App\Events\IcalConflictDetected;
use App\Events\StayScheduleChanged;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use App\Services\Ical\IcalCalendarMerger;
use App\Services\Ical\ImportedEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
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

it('montre au tableau de bord une arrivée Booking.com, pas une fermeture redondante', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-16', '2026-11-18')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, [
        'B1' => ['2026-11-15', '2026-11-16', 'CLOSED - Not available'],
        'B2' => ['2026-11-16', '2026-11-18', 'CLOSED - Not available'],
    ]);

    $movements = collect($this->getJson('/api/admin/dashboard')->json('data.movements'))->keyBy('date');

    expect(array_column($movements['2026-11-15']['arrivals'], 'label'))->toBe(['Booking.com'])
        ->and($movements['2026-11-16']['arrivals'])->toBe([]);
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

it('prend pour un écho une date Booking.com posée sur une réservation et une fermeture du site', function () {
    Event::fake([IcalConflictDetected::class]);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    directBooking($this->apartment, '2026-11-22', '2026-11-24');

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-24', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
    Event::assertNotDispatched(IcalConflictDetected::class);
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

it('prévient le ménage du séjour Booking.com caché sous une fermeture du site retirée', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => '2026-11-20', 'ends_on' => '2026-11-23', 'needs_cleaning' => true])->assertCreated();
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);
    $manual = AvailabilityBlock::query()->where('type', BlockType::Manual)->sole();
    Event::fake([StayScheduleChanged::class]);

    $this->deleteJson("/api/admin/blocks/{$manual->id}")->assertSuccessful();

    $changes = Event::dispatched(StayScheduleChanged::class)->map(fn (array $args) => [$args[0]->change->kind, $args[0]->change->blockId])->all();
    expect($changes)->toEqualCanonicalizing([
        [StayChangeKind::Added, importedBlock('B1')->id],   // la date Booking.com redevient un séjour possible
        [StayChangeKind::Cancelled, $manual->id],           // fin de l'occupation par des proches
    ]);
});

it('retire du planning du ménage un séjour Booking.com déplacé sur une fermeture du site', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-12-01', '2026-12-04')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);
    Event::fake([StayScheduleChanged::class]);

    feedNow($this->booking, ['B1' => ['2026-12-01', '2026-12-04', 'CLOSED - Not available']]);

    expect(importedBlock('B1')->is_redundant_closure)->toBeTrue();
    Event::assertDispatchedTimes(StayScheduleChanged::class, 1);
    Event::assertDispatched(StayScheduleChanged::class, fn (StayScheduleChanged $event) => $event->change->kind === StayChangeKind::Cancelled
        && $event->change->startsOn->toDateString() === '2026-11-20' && $event->change->endsOn->toDateString() === '2026-11-23');
});

it('compte au planning un séjour Booking.com comme vendu, sa date répétant une fermeture comme fermée', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual]);
    feedNow($this->booking, [
        'B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available'],
        'B2' => ['2026-11-25', '2026-11-27', 'CLOSED - Not available'],
    ]);

    $this->getJson('/api/admin/planning?from=2026-11-13&days=30')
        ->assertJsonPath('data.figures.sold_nights', 2)
        ->assertJsonPath('data.figures.open_nights', 27)
        ->assertJsonPath('data.figures.arrivals', 1);
});

it('prend la date fermée Airbnb posée sur une réservation du site pour un écho : ni conflit ni alerte', function () {
    directBooking($this->apartment, '2026-11-20', '2026-11-23');
    Event::fake([IcalConflictDetected::class]);

    feedNow($this->airbnb, ['A1' => ['2026-11-20', '2026-11-23', 'Airbnb (Not available)']]);

    expect(importedBlock('A1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
    Event::assertNotDispatched(IcalConflictDetected::class);
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 0);
});

it('ne fait pas expirer une réservation en cours de paiement devant une simple date fermée Airbnb', function () {
    $pending = directBooking($this->apartment, '2026-11-20', '2026-11-23', BookingStatus::Pending);

    feedNow($this->airbnb, ['A1' => ['2026-11-20', '2026-11-23', 'Airbnb (Not available)']]);

    expect($pending->fresh()->status)->toBe(BookingStatus::Pending);
});

it('ne fait pas expirer une réservation en cours de paiement devant l’écho Booking.com de ses nuits', function () {
    $pending = directBooking($this->apartment, '2026-11-20', '2026-11-23', BookingStatus::Pending);

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);

    expect($pending->fresh()->status)->toBe(BookingStatus::Pending)
        ->and(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
});

it('fait expirer une réservation en cours de paiement devant une date Booking.com qui déborde sur des nuits libres', function () {
    $pending = directBooking($this->apartment, '2026-11-20', '2026-11-23', BookingStatus::Pending);

    feedNow($this->booking, ['B1' => ['2026-11-22', '2026-11-25', 'CLOSED - Not available']]);

    expect($pending->fresh()->status)->toBe(BookingStatus::Expired)
        ->and(importedBlock('B1'))->has_conflict->toBeFalse()->is_redundant_closure->toBeFalse();
});

it('prend pour un écho une date Booking.com posée sur un séjour Airbnb (exporté par le site)', function () {
    feedNow($this->airbnb, ['A1' => ['2026-11-20', '2026-11-23', 'Reserved']]);

    feedNow($this->booking, ['B1' => ['2026-11-20', '2026-11-23', 'CLOSED - Not available']]);

    expect(importedBlock('B1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeTrue();
    $this->getJson('/api/admin/overview')->assertJsonPath('data.conflicts', 0);
});

it('garde l’alerte et l’expiration pour un vrai séjour Airbnb sur une réservation du site', function () {
    directBooking($this->apartment, '2026-11-20', '2026-11-23');
    $pending = directBooking($this->apartment, '2026-11-25', '2026-11-27', BookingStatus::Pending);
    Event::fake([IcalConflictDetected::class]);

    feedNow($this->airbnb, [
        'A1' => ['2026-11-20', '2026-11-23', 'Reserved'],
        'A2' => ['2026-11-25', '2026-11-27', 'Reserved'],
    ]);

    expect(importedBlock('A1'))->has_conflict->toBeTrue()->is_redundant_closure->toBeFalse()
        ->and($pending->fresh()->status)->toBe(BookingStatus::Expired);
    Event::assertDispatched(IcalConflictDetected::class);
});
