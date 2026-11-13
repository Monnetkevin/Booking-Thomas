<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Mail\IcalConflictAlert;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Availability\BlockConflictService;
use App\Services\Ical\IcalCalendarMerger;
use App\Services\Ical\ImportedEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-13 10:00:00');
    Mail::fake();
    app(SettingRepositoryInterface::class)->put('notifications.admin_emails', ['hote@example.com']);
    $this->apartment = Apartment::factory()->create();
    $this->feed = IcalFeed::factory()->for($this->apartment)->create();
});

/** Le flux Airbnb contient désormais ces séjours : UID → [arrivée, départ]. */
function airbnbNow(array $stays): void
{
    $events = array_map(
        fn (string $uid, array $dates) => new ImportedEvent($uid, CarbonImmutable::parse($dates[0]), CarbonImmutable::parse($dates[1]), 'Reserved', "https://www.airbnb.com/hosting/reservations/details/$uid"),
        array_keys($stays),
        $stays,
    );
    app(IcalCalendarMerger::class)->merge(test()->feed, $events);
}

function airbnbBlock(string $uid): AvailabilityBlock
{
    return AvailabilityBlock::query()->where('external_uid', $uid)->firstOrFail();
}

it('alerte l’hôte quand Airbnb recouvre une réservation directe confirmée', function () {
    $booking = directBooking($this->apartment, '2026-11-23', '2026-11-26');

    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);

    expect(airbnbBlock('HM1')->has_conflict)->toBeTrue()
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    Mail::assertQueued(IcalConflictAlert::class, function (IcalConflictAlert $mail) use ($booking) {
        $html = $mail->render();

        return $mail->hasTo('hote@example.com')
            && str_contains($html, $booking->reference)
            && str_contains($html, 'https://www.airbnb.com/hosting/reservations/details/HM1')
            && $mail->envelope()->subject === "Conflit de dates : {$this->apartment->getTranslation('name', 'fr')}, sam. 21 nov. 2026 → mer. 25 nov. 2026";
    });
});

it('expire sur-le-champ une réservation directe encore en attente de paiement, sans conflit ni alerte', function () {
    $pending = directBooking($this->apartment, '2026-11-23', '2026-11-26', BookingStatus::Pending);

    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);

    expect($pending->fresh()->status)->toBe(BookingStatus::Expired)
        ->and(AvailabilityBlock::query()->where('booking_id', $pending->id)->value('is_active'))->toBeFalse()
        ->and(airbnbBlock('HM1')->has_conflict)->toBeFalse();
    Mail::assertNothingQueued();
});

it('signale sans alerte un recouvrement avec un blocage manuel', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-22', '2026-11-24')->create(['type' => BlockType::Manual]);

    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);

    expect(airbnbBlock('HM1')->has_conflict)->toBeTrue();
    Mail::assertNothingQueued();
});

it('n’alerte pas de nouveau à chaque lecture d’un conflit déjà signalé', function () {
    directBooking($this->apartment, '2026-11-23', '2026-11-26');

    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);
    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25'], 'HM2' => ['2026-12-10', '2026-12-12']]);

    Mail::assertQueuedCount(1);
    expect(airbnbBlock('HM1')->has_conflict)->toBeTrue();
});

it('lève le conflit quand le séjour Airbnb est déplacé hors des nuits occupées', function () {
    directBooking($this->apartment, '2026-11-23', '2026-11-26');
    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);

    airbnbNow(['HM1' => ['2026-11-26', '2026-11-29']]);

    expect(airbnbBlock('HM1'))->has_conflict->toBeFalse()->is_active->toBeTrue();
});

it('lève le conflit quand l’autre séjour disparaît du flux, le plus ancien l’emportant', function () {
    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25'], 'BLOCK' => ['2026-11-24', '2026-11-28']]);
    expect(airbnbBlock('HM1')->has_conflict)->toBeFalse()
        ->and(airbnbBlock('BLOCK')->has_conflict)->toBeTrue();

    airbnbNow(['BLOCK' => ['2026-11-24', '2026-11-28']]);

    expect(airbnbBlock('HM1')->is_active)->toBeFalse()
        ->and(airbnbBlock('BLOCK'))->has_conflict->toBeFalse()->is_active->toBeTrue();
});

it('lève le conflit quand les nuits de la réservation directe sont libérées (annulation)', function () {
    $booking = directBooking($this->apartment, '2026-11-23', '2026-11-26');
    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);

    // Annulation par l'hôte (jalon 6) : nuits libérées, puis réévaluation des conflits.
    AvailabilityBlock::query()->where('booking_id', $booking->id)->update(['is_active' => false]);
    $resolved = app(BlockConflictService::class)->release($this->apartment->id, $booking->arrival, $booking->departure);

    expect($resolved)->toBe(1)
        ->and(airbnbBlock('HM1')->has_conflict)->toBeFalse();
});

it('lève le conflit quand la réservation en attente qui le portait expire', function () {
    // Bloc Airbnb déjà en conflit avec un blocage manuel, et une réservation en attente plus loin.
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-22', '2026-11-24')->create(['type' => BlockType::Manual]);
    airbnbNow(['HM1' => ['2026-11-21', '2026-11-25']]);
    AvailabilityBlock::query()->where('type', BlockType::Manual)->update(['is_active' => false]);
    $pending = directBooking($this->apartment, '2026-11-22', '2026-11-24', BookingStatus::Pending);
    Date::setTestNow('2026-11-13 11:00:00');

    $this->artisan('bookings:expire')->assertSuccessful();

    expect($pending->fresh()->status)->toBe(BookingStatus::Expired)
        ->and(airbnbBlock('HM1')->has_conflict)->toBeFalse();
});
