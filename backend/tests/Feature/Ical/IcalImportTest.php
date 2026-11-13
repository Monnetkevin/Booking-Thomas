<?php

use App\Enums\BlockType;
use App\Enums\IcalSyncStatus;
use App\Jobs\SyncIcalFeed;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\RatePeriod;
use App\Services\Ical\IcalImportService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Date::setTestNow('2026-11-13 10:00:00');
    Sleep::fake();
    $this->apartment = Apartment::factory()->create();
    $this->feed = IcalFeed::factory()->for($this->apartment)->create([
        'import_url' => 'https://www.airbnb.fr/calendar/ical/123.ics?s=secret',
    ]);
    $this->sync = fn () => app(IcalImportService::class)->sync($this->feed->fresh());

    // Un seul faux serveur Airbnb, dont la réponse change d'un appel de airbnbServes() à l'autre.
    $served = $this->airbnb = new ArrayObject(['content' => '', 'etag' => '', 'status' => 200]);
    Http::fake(['www.airbnb.fr/*' => function () use ($served) {
        if ($served['status'] === 0) {
            throw new ConnectionException('Délai dépassé');
        }

        return Http::response($served['content'], $served['status'], array_filter(['ETag' => $served['etag']]));
    }]);
});

/** Le flux Airbnb répond désormais ce contenu, avec cet ETag (statut 0 : injoignable). */
function airbnbServes(string $content, string $etag = '"v1"', int $status = 200): void
{
    test()->airbnb->exchangeArray(['content' => $content, 'etag' => $etag, 'status' => $status]);
}

/** @return array<string, array{0: string, 1: string, 2: bool}> UID → [arrivée, départ, actif] */
function feedNights(IcalFeed $feed): array
{
    return AvailabilityBlock::query()->where('ical_feed_id', $feed->id)->orderBy('starts_on')->get()
        ->mapWithKeys(fn (AvailabilityBlock $b) => [$b->external_uid => [$b->starts_on->toDateString(), $b->ends_on->toDateString(), $b->is_active]])
        ->all();
}

it('bloque les nuits des réservations Airbnb à venir, avec le lien vers la réservation', function () {
    airbnbServes(icalFixture('airbnb'));

    $result = ($this->sync)();

    expect($result->status)->toBe(IcalSyncStatus::Success)
        ->and($result->created)->toBe(2)
        ->and(array_values(feedNights($this->feed)))->toBe([
            ['2026-11-21', '2026-11-25', true],
            ['2026-12-03', '2026-12-05', true],
        ]);
    $reservation = AvailabilityBlock::query()->where('starts_on', '2026-11-21')->firstOrFail();
    expect($reservation->type)->toBe(BlockType::Ical)
        ->and($reservation->apartment_id)->toBe($this->apartment->id)
        ->and($reservation->note)->toBe('Reserved')
        // Lien seul : jamais les chiffres de téléphone du voyageur.
        ->and($reservation->external_url)->toBe('https://www.airbnb.com/hosting/reservations/details/HMABCD1234');
    expect($this->feed->fresh())
        ->last_status->toBe(IcalSyncStatus::Success)
        ->etag->toBe('"v1"')
        ->content_hash->toBe(hash('sha256', icalFixture('airbnb')))
        ->last_synced_at->not->toBeNull();
});

it('rend les nuits indisponibles sur le calendrier du site', function () {
    airbnbServes(icalFixture('airbnb'));
    $this->apartment->update(['status' => 'published', 'turnover_days' => 0]);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-12-31']);

    ($this->sync)();

    $this->getJson("/api/apartments/{$this->apartment->slug}/availability?from=2026-11-20&to=2026-11-27")
        ->assertJsonPath('data.unavailable', ['2026-11-21', '2026-11-22', '2026-11-23', '2026-11-24']);
});

it('ne fait rien si Airbnb répond 304, avec l’ETag de la lecture précédente', function () {
    $this->feed->update(['etag' => '"v1"']);
    airbnbServes('', '"v1"', 304);

    expect(($this->sync)()->status)->toBe(IcalSyncStatus::Unchanged);

    Http::assertSent(fn (Request $request) => $request->header('If-None-Match') === ['"v1"']);
    expect(AvailabilityBlock::count())->toBe(0);
});

it('ne relit pas un contenu identique au précédent', function () {
    airbnbServes(icalFixture('airbnb'));
    ($this->sync)();
    AvailabilityBlock::query()->delete();

    expect(($this->sync)()->status)->toBe(IcalSyncStatus::Unchanged)
        ->and(AvailabilityBlock::count())->toBe(0);
});

it('déplace un séjour modifié et libère les nuits d’un séjour annulé chez Airbnb', function () {
    airbnbServes(icalFixture('airbnb'));
    ($this->sync)();

    airbnbServes(icalFixture('airbnb-modified'), '"v2"');
    $result = ($this->sync)();

    expect($result)->updated->toBe(1)->removed->toBe(1)->created->toBe(0)
        ->and(array_values(feedNights($this->feed)))->toBe([
            ['2026-11-22', '2026-11-26', true],
            ['2026-12-03', '2026-12-05', false],
        ]);
});

it('laisse les séjours terminés tels quels', function () {
    $past = AvailabilityBlock::factory()->for($this->apartment)->between('2026-10-01', '2026-10-04')->create([
        'type' => BlockType::Ical, 'ical_feed_id' => $this->feed->id, 'external_uid' => 'ancien@airbnb.com',
    ]);
    airbnbServes(icalFixture('airbnb'));

    ($this->sync)();

    expect($past->fresh()->is_active)->toBeTrue()
        ->and(AvailabilityBlock::query()->where('starts_on', '2026-10-27')->exists())->toBeFalse();
});

describe('un flux en panne ou vide ne libère aucune nuit', function () {
    beforeEach(function () {
        airbnbServes(icalFixture('airbnb'));
        ($this->sync)();
        $this->before = feedNights($this->feed);
    });

    it('flux soudain vide', function () {
        airbnbServes(icalFixture('airbnb-empty'), '"v2"');

        $result = ($this->sync)();

        expect($result->status)->toBe(IcalSyncStatus::Failed)
            ->and($result->error)->toContain('vide')
            ->and(feedNights($this->feed))->toBe($this->before);
    });

    it('fichier invalide', function () {
        airbnbServes('<html>Maintenance</html>', '"v2"');

        expect(($this->sync)()->status)->toBe(IcalSyncStatus::Failed)
            ->and(feedNights($this->feed))->toBe($this->before);
    });

    it('erreur du serveur, après 3 tentatives espacées', function () {
        airbnbServes('', '', 503);

        $result = ($this->sync)();

        expect($result->status)->toBe(IcalSyncStatus::Failed)
            ->and($result->error)->toContain('503')
            ->and(feedNights($this->feed))->toBe($this->before);
        Http::assertSentCount(4); // la première lecture, puis 3 tentatives
        expect($this->feed->fresh())->consecutive_failures->toBe(1)->last_error->toContain('503')
            ->and($this->feed->fresh()->content_hash)->toBe(hash('sha256', icalFixture('airbnb')));
    });

    it('plateforme injoignable', function () {
        airbnbServes('', '', 0);

        expect(($this->sync)()->status)->toBe(IcalSyncStatus::Failed)
            ->and(feedNights($this->feed))->toBe($this->before);
    });
});

it('remet le compteur d’échecs à zéro après une lecture réussie', function () {
    $this->feed->update(['consecutive_failures' => 2, 'last_error' => 'Erreur']);
    airbnbServes(icalFixture('airbnb'));

    ($this->sync)();

    expect($this->feed->fresh())->consecutive_failures->toBe(0)->last_error->toBeNull();
});

it('enregistre en conflit, sans la refuser, une réservation Airbnb qui recouvre une réservation directe', function () {
    $booking = directBooking($this->apartment, '2026-11-23', '2026-11-26');
    airbnbServes(icalFixture('airbnb'));

    $result = ($this->sync)();

    expect($result->conflicts)->toBe(1)
        ->and(AvailabilityBlock::query()->where('external_uid', 'like', '1418fb94e984%')->value('has_conflict'))->toBeTrue()
        ->and(AvailabilityBlock::query()->where('booking_id', $booking->id)->value('has_conflict'))->toBeFalse();
});

it('relit tous les calendriers actifs dotés d’une URL, toutes les 15 minutes', function () {
    Queue::fake();
    IcalFeed::factory()->create(['import_url' => null]);
    IcalFeed::factory()->create(['is_active' => false]);

    $this->artisan('ical:sync')->expectsOutputToContain('1 calendrier(s)')->assertSuccessful();

    Queue::assertPushed(SyncIcalFeed::class, 1);
    Queue::assertPushed(SyncIcalFeed::class, fn (SyncIcalFeed $job) => $job->feed->is($this->feed));
    $this->artisan('schedule:list')->expectsOutputToContain('ical:sync');
});
