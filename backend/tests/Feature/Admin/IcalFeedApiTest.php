<?php

use App\Enums\IcalSyncStatus;
use App\Jobs\SyncIcalFeed;
use App\Models\Apartment;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create();
});

const AIRBNB_URL = 'https://www.airbnb.fr/calendar/ical/123.ics?s=secret';

it('ajoute le calendrier Airbnb d’un logement et le relit aussitôt', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb', 'import_url' => AIRBNB_URL])
        ->assertCreated()
        ->assertJsonPath('data.provider', 'airbnb')
        ->assertJsonPath('data.provider_label', 'Airbnb')
        ->assertJsonPath('data.import_url', AIRBNB_URL)
        ->assertJsonPath('data.last_status', null);

    $feed = IcalFeed::firstOrFail();
    expect(strlen($feed->export_token))->toBe(40);
    Queue::assertPushed(SyncIcalFeed::class, fn (SyncIcalFeed $job) => $job->feed->is($feed));
});

it('accepte un calendrier sans URL d’import (export seul)', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb', 'import_url' => ''])
        ->assertCreated()
        ->assertJsonPath('data.import_url', null);
    Queue::assertNothingPushed();
});

it('refuse une URL d’import hors d’Airbnb ou sans HTTPS', function (string $url) {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb', 'import_url' => $url])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('import_url');
})->with([
    'autre site' => 'https://exemple.fr/calendar.ics',
    'faux domaine' => 'https://airbnb.com.exemple.fr/calendar.ics',
    'réseau interne' => 'https://127.0.0.1/calendar.ics',
    'sans HTTPS' => 'http://www.airbnb.fr/calendar/ical/123.ics',
]);

it('accepte les domaines Airbnb nationaux', function (string $url) {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb', 'import_url' => $url])
        ->assertCreated();
})->with(['https://www.airbnb.com/calendar/ical/1.ics', 'https://fr.airbnb.ca/calendar/ical/1.ics', 'https://www.airbnb.co.uk/calendar/ical/1.ics']);

it('n’ajoute qu’un calendrier par plateforme et par logement', function () {
    IcalFeed::factory()->for($this->apartment)->create();

    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb'])
        ->assertConflict()
        ->assertJsonPath('code', 'ical_feed_already_exists');
});

it('repart de zéro quand l’URL d’import change', function () {
    $feed = IcalFeed::factory()->for($this->apartment)->create([
        'etag' => '"v1"', 'content_hash' => 'abc', 'last_status' => IcalSyncStatus::Failed, 'last_error' => 'Erreur', 'consecutive_failures' => 2,
    ]);

    $this->patchJson("/api/admin/ical-feeds/{$feed->id}", ['import_url' => 'https://www.airbnb.fr/calendar/ical/456.ics'])
        ->assertOk()
        ->assertJsonPath('data.last_status', null);

    expect($feed->fresh())->etag->toBeNull()->content_hash->toBeNull()->consecutive_failures->toBe(0);
    Queue::assertPushed(SyncIcalFeed::class);
});

it('désactive un calendrier sans libérer ses nuits ni le relire', function () {
    $feed = IcalFeed::factory()->for($this->apartment)->create(['last_status' => IcalSyncStatus::Success]);

    $this->patchJson("/api/admin/ical-feeds/{$feed->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
    Queue::assertNothingPushed();
});

it('liste les calendriers sans exposer le token d’export dans les champs bruts', function () {
    IcalFeed::factory()->for($this->apartment)->create();

    $this->getJson('/api/admin/ical-feeds')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonMissingPath('data.0.export_token');
});

it('réserve les calendriers aux administrateurs', function () {
    $this->actingAs(User::factory()->housekeeping()->create());
    $feed = IcalFeed::factory()->for($this->apartment)->create();

    $this->getJson('/api/admin/ical-feeds')->assertForbidden();
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'airbnb'])->assertForbidden();
    $this->patchJson("/api/admin/ical-feeds/{$feed->id}", ['is_active' => false])->assertForbidden();
});

it('relit tous les calendriers importables en file d’attente', function () {
    IcalFeed::factory()->for($this->apartment)->create(['import_url' => AIRBNB_URL]);
    IcalFeed::factory()->for(Apartment::factory())->create(['import_url' => AIRBNB_URL, 'is_active' => false]);
    IcalFeed::factory()->for(Apartment::factory())->create(['import_url' => null]);

    $this->postJson('/api/admin/ical-feeds/sync-all')->assertAccepted()->assertJsonPath('data.queued', 1);

    Queue::assertPushed(SyncIcalFeed::class, 1);
    $this->actingAs(User::factory()->housekeeping()->create())->postJson('/api/admin/ical-feeds/sync-all')->assertForbidden();
});

it('donne le journal récent de tous les calendriers, avec le logement', function () {
    $this->apartment->update(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon']]);
    $feed = IcalFeed::factory()->for($this->apartment)->create();
    IcalSyncLog::query()->forceCreate(['ical_feed_id' => $feed->id, 'trigger' => 'scheduled', 'status' => 'failed', 'error' => 'HTTP 403', 'created_at' => now()->subHour()]);
    IcalSyncLog::query()->forceCreate(['ical_feed_id' => $feed->id, 'trigger' => 'manual', 'status' => 'success', 'created_at' => now()]);

    $this->getJson('/api/admin/ical-sync-logs')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.status', 'success')
        ->assertJsonPath('data.0.apartment', 'Le Cocon')
        ->assertJsonPath('data.0.provider', 'Airbnb')
        ->assertJsonPath('data.1.error', 'HTTP 403');
});

it('ajoute aussi le calendrier Booking.com du logement, à côté de celui d’Airbnb', function () {
    IcalFeed::factory()->for($this->apartment)->create();

    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'booking', 'import_url' => 'https://admin.booking.com/hotel/hoteladmin/ical.html?t=secret'])
        ->assertCreated()
        ->assertJsonPath('data.provider', 'booking')
        ->assertJsonPath('data.provider_label', 'Booking.com');
    expect($this->apartment->icalFeeds()->count())->toBe(2);
});

it('refuse une URL Booking.com hors du domaine de Booking.com', function (string $url) {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/ical-feeds", ['provider' => 'booking', 'import_url' => $url])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('import_url');
})->with([
    'faux domaine' => 'https://admin.booking.com.exemple.fr/ical.html',
    'domaine voisin' => 'https://notbooking.com/ical.html',
    'Airbnb' => 'https://www.airbnb.fr/calendar/ical/1.ics',
]);
