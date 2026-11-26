<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Enums\NotificationLevel;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\User;
use App\Notifications\Channels\InAppChannel;
use App\Notifications\Housekeeping\DailySummaryNotification;
use App\Notifications\Housekeeping\StayChangedNotification;
use App\Services\Booking\BookingService;
use App\Services\Ical\IcalCalendarMerger;
use App\Services\Ical\ImportedEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Date::setTestNow('2026-11-19 10:00:00');
    Mail::fake();
    Notification::fake();
    $this->host = User::factory()->create();
    $this->cleaner = User::factory()->housekeeping()->create();
    $this->apartment = Apartment::factory()->create([
        'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'], 'check_in_time' => '16:00:00', 'check_out_time' => '10:30:00',
    ]);
    $this->feed = IcalFeed::factory()->for($this->apartment)->create();
});

/** Le flux Airbnb du logement contient désormais ces séjours : UID → [arrivée, départ]. */
function airbnbStays(array $stays): void
{
    app(IcalCalendarMerger::class)->merge(test()->feed, array_map(
        fn (string $uid, array $dates) => new ImportedEvent($uid, CarbonImmutable::parse($dates[0]), CarbonImmutable::parse($dates[1]), 'Reserved', null),
        array_keys($stays),
        $stays,
    ));
}

/** @return list<StayChangedNotification> */
function stayNotifications(User $user): array
{
    return Notification::sent($user, StayChangedNotification::class)->all();
}

it('prévient le ménage d’un séjour Airbnb ajouté dans les 14 jours, pas l’hôte', function () {
    airbnbStays(['HM1' => ['2026-11-25', '2026-11-28']]);

    [$notification] = stayNotifications($this->cleaner);
    expect($notification->title())->toBe('Nouveau séjour : Le Cocon')
        ->and($notification->body())->toBe('Arrivée mer. 25 nov. 2026, départ sam. 28 nov. 2026.')
        ->and($notification->level())->toBe(NotificationLevel::Info)
        ->and($notification->via($this->cleaner))->toBe([InAppChannel::class]);
    Notification::assertNotSentTo($this->host, StayChangedNotification::class);
});

it('rend urgent un changement pour aujourd’hui ou demain, envoyé aussi par e-mail', function () {
    airbnbStays(['HM1' => ['2026-11-20', '2026-11-23']]);

    [$notification] = stayNotifications($this->cleaner);
    expect($notification->level())->toBe(NotificationLevel::Urgent)
        ->and($notification->via($this->cleaner))->toContain('mail');
});

it('ignore un séjour au-delà des 14 prochains jours', function () {
    airbnbStays(['HM1' => ['2026-12-10', '2026-12-12']]);

    expect(stayNotifications($this->cleaner))->toBe([]);
});

it('prévient d’un séjour déplacé, avec ses anciennes dates, puis annulé chez la plateforme', function () {
    airbnbStays(['HM1' => ['2026-11-25', '2026-11-28'], 'HM2' => ['2026-12-20', '2026-12-22']]);
    airbnbStays(['HM1' => ['2026-11-26', '2026-11-29'], 'HM2' => ['2026-12-20', '2026-12-22']]);
    airbnbStays(['HM2' => ['2026-12-20', '2026-12-22']]);

    $notifications = stayNotifications($this->cleaner);
    expect(array_map(fn (StayChangedNotification $n) => $n->change->kind->value, $notifications))->toBe(['added', 'moved', 'cancelled'])
        ->and($notifications[1]->body())->toContain('Avant : arrivée mer. 25 nov. 2026, départ sam. 28 nov. 2026.')
        ->and($notifications[2]->title())->toBe('Séjour annulé : Le Cocon');
});

it('signale un départ et une arrivée le même jour', function () {
    directBooking($this->apartment, '2026-11-22', '2026-11-25');

    airbnbStays(['HM1' => ['2026-11-25', '2026-11-28']]);

    expect(stayNotifications($this->cleaner)[0]->body())->toContain('Départ et arrivée le même jour');
});

it('prévient le ménage d’une réservation directe confirmée, sans nom ni montant', function () {
    $booking = directBooking($this->apartment, '2026-11-24', '2026-11-27', BookingStatus::Pending);
    $booking->update(['guest_name' => 'Camille Martin', 'adults' => 2, 'children' => 1, 'total_amount' => 48500, 'expires_at' => now()->addMinutes(30)]);

    app(BookingService::class)->confirm($booking->fresh());

    [$notification] = stayNotifications($this->cleaner);
    $content = $notification->title().' '.$notification->body().' '.implode(' ', $notification->details());
    expect($content)->toContain('3 voyageur(s)')
        ->not->toContain('Camille')->not->toContain('485')->not->toContain('@')->not->toContain('Airbnb');
});

it('ne prévient pas le ménage d’une date fermée sur Airbnb, ni de sa disparition', function () {
    app(IcalCalendarMerger::class)->merge($this->feed, [new ImportedEvent('NA1', CarbonImmutable::parse('2026-11-25'), CarbonImmutable::parse('2026-11-28'), 'Airbnb (Not available)', null)]);
    app(IcalCalendarMerger::class)->merge($this->feed, [], acceptEmpty: true);

    expect(stayNotifications($this->cleaner))->toBe([]);
});

it('ne prévient pas le ménage d’une date Booking.com posée sur une fermeture du site', function () {
    $booking = IcalFeed::factory()->for($this->apartment)->create(['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/ical.ics?t=x']);
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-25', '2026-11-28')->create(['type' => BlockType::Manual]);

    app(IcalCalendarMerger::class)->merge($booking, [new ImportedEvent('B1', CarbonImmutable::parse('2026-11-25'), CarbonImmutable::parse('2026-11-28'), 'CLOSED - Not available', null)]);

    expect(stayNotifications($this->cleaner))->toBe([]);
});

it('prévient le ménage d’une fermeture d’un logement occupé, à la création comme au retrait', function () {
    $this->actingAs($this->host);

    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => '2026-11-25', 'ends_on' => '2026-11-28', 'note' => 'Famille', 'needs_cleaning' => true])
        ->assertCreated()
        ->assertJsonPath('data.needs_cleaning', true);
    $block = AvailabilityBlock::query()->where('type', BlockType::Manual)->sole();
    $this->deleteJson("/api/admin/blocks/{$block->id}")->assertNoContent();

    [$added, $cancelled] = stayNotifications($this->cleaner);
    expect($added->title())->toBe('Nouveau séjour : Le Cocon')
        ->and($added->body())->toContain('départ sam. 28 nov.')->not->toContain('Famille')
        ->and($cancelled->title())->toBe('Séjour annulé : Le Cocon');
});

it('ne prévient pas le ménage d’une fermeture sans occupant', function () {
    $this->actingAs($this->host);

    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => '2026-11-25', 'ends_on' => '2026-11-28'])
        ->assertCreated()
        ->assertJsonPath('data.needs_cleaning', false);

    expect(stayNotifications($this->cleaner))->toBe([]);
});

it('signale un séjour qui arrive le jour où des proches libèrent le logement', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-22', '2026-11-25')->create(['type' => BlockType::Manual, 'needs_cleaning' => true]);

    airbnbStays(['HM1' => ['2026-11-25', '2026-11-28']]);

    expect(stayNotifications($this->cleaner)[0]->body())->toContain('Départ et arrivée le même jour');
});

it('ne voit pas de ménage entre deux séjours après une simple fermeture', function () {
    AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-22', '2026-11-25')->create(['type' => BlockType::Manual]);

    airbnbStays(['HM1' => ['2026-11-25', '2026-11-28']]);

    expect(stayNotifications($this->cleaner)[0]->body())->not->toContain('Départ et arrivée le même jour');
});

describe('récapitulatif du lendemain : fermetures', function () {
    it('annonce l’occupation par des proches et le ménage à leur départ, et une date Airbnb fermée comme une fermeture', function () {
        $nest = Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'The Nest']]);
        AvailabilityBlock::factory()->for($this->apartment)->between('2026-11-17', '2026-11-20')->create(['type' => BlockType::Manual, 'needs_cleaning' => true]);
        AvailabilityBlock::factory()->for($nest)->between('2026-11-20', '2026-11-23')->create(['type' => BlockType::Manual, 'needs_cleaning' => true]);
        $nestFeed = IcalFeed::factory()->for($nest)->create();
        AvailabilityBlock::factory()->for($nest)->between('2026-11-15', '2026-11-20')->create(['type' => BlockType::Ical, 'ical_feed_id' => $nestFeed->id, 'external_uid' => 'NA', 'note' => 'Airbnb (Not available)']);
        $other = Apartment::factory()->create(['name' => ['fr' => 'La Cabane', 'en' => 'Hut']]);
        $otherFeed = IcalFeed::factory()->for($other)->create();
        AvailabilityBlock::factory()->for($other)->between('2026-11-20', '2026-11-24')->create(['type' => BlockType::Ical, 'ical_feed_id' => $otherFeed->id, 'external_uid' => 'NB', 'note' => 'Airbnb (Not available)']);
        Date::setTestNow('2026-11-19 18:00:00');

        $this->artisan('notifications:housekeeping-summary')->assertSuccessful();

        Notification::assertSentTo($this->cleaner, DailySummaryNotification::class, function (DailySummaryNotification $summary) {
            expect($summary->details())->toEqualCanonicalizing([
                'Le Cocon : fin d’occupation par des proches, ménage à prévoir',
                'Le Nid : occupé par des proches à partir de demain',
                'La Cabane : fermé à partir de demain, pas d’arrivée',
            ]);

            return true;
        });
    });
});

describe('récapitulatif du lendemain', function () {
    beforeEach(function () {
        // Demain (20 nov.) : un départ, une arrivée directe de 3 voyageurs, une fermeture.
        directBooking($this->apartment, '2026-11-17', '2026-11-20');
        directBooking($this->apartment, '2026-11-20', '2026-11-23')->update(['adults' => 2, 'children' => 1]);
        AvailabilityBlock::factory()->for(Apartment::factory()->create(['name' => ['fr' => 'Le Nid', 'en' => 'The Nest']]))
            ->between('2026-11-20', '2026-11-22')->create(['type' => BlockType::Manual]);
    });

    it('envoie à 18 h, par défaut, les départs et arrivées de demain', function () {
        Date::setTestNow('2026-11-19 18:00:00');

        $this->artisan('notifications:housekeeping-summary')->expectsOutputToContain('1 compte(s)')->assertSuccessful();

        Notification::assertSentTo($this->cleaner, DailySummaryNotification::class, function (DailySummaryNotification $summary, array $channels) {
            return $summary->title() === 'Demain, ven. 20 nov. 2026 : 3 mouvement(s)'
                && in_array('mail', $channels, true)
                && in_array('Le Cocon : départ avant 10 h 30', $summary->details(), true)
                && in_array('Le Cocon : arrivée à partir de 16 h, 3 voyageur(s)', $summary->details(), true)
                && in_array('Le Nid : fermé à partir de demain, pas d’arrivée', $summary->details(), true);
        });
        Notification::assertNotSentTo($this->host, DailySummaryNotification::class);
    });

    it('respecte l’heure choisie et le récapitulatif coupé', function () {
        $this->cleaner->update(['notification_preferences' => ['summary' => ['enabled' => true, 'hour' => 20]]]);
        $off = User::factory()->housekeeping()->create(['notification_preferences' => ['summary' => ['enabled' => false, 'hour' => 18]]]);
        Date::setTestNow('2026-11-19 18:00:00');

        $this->artisan('notifications:housekeeping-summary')->assertSuccessful();
        Notification::assertNothingSent();

        Date::setTestNow('2026-11-19 20:00:00');
        $this->artisan('notifications:housekeeping-summary')->assertSuccessful();
        Notification::assertSentTo($this->cleaner, DailySummaryNotification::class);
        Notification::assertNotSentTo($off, DailySummaryNotification::class);
    });

    it('ignore les réservations en attente de paiement et les logements supprimés', function () {
        // Réservation commencée mais pas payée, et logement supprimé qui garde un séjour importé.
        directBooking(Apartment::factory()->create(), '2026-11-20', '2026-11-22', BookingStatus::Pending);
        $deleted = Apartment::factory()->create();
        AvailabilityBlock::factory()->for($deleted)->between('2026-11-20', '2026-11-24')->create(['type' => BlockType::Ical]);
        $deleted->delete();
        Date::setTestNow('2026-11-19 18:00:00');

        $this->artisan('notifications:housekeeping-summary')->assertSuccessful();

        Notification::assertSentTo($this->cleaner, DailySummaryNotification::class, fn (DailySummaryNotification $summary) => count($summary->movements) === 3);
    });

    it('n’envoie rien s’il n’y a aucun mouvement demain', function () {
        Date::setTestNow('2026-11-24 18:00:00');

        $this->artisan('notifications:housekeeping-summary')->expectsOutputToContain('0 compte(s)')->assertSuccessful();

        Notification::assertNothingSent();
    });
});
