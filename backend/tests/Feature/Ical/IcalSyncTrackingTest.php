<?php

use App\Enums\IcalSyncStatus;
use App\Enums\IcalSyncTrigger;
use App\Mail\IcalFeedFailingAlert;
use App\Models\Apartment;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Ical\IcalImportService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Date::setTestNow('2026-11-13 10:00:00');
    Sleep::fake();
    Mail::fake();
    app(SettingRepositoryInterface::class)->put('notifications.admin_emails', ['hote@example.com']);
    $this->feed = IcalFeed::factory()->for(Apartment::factory())->create([
        'import_url' => 'https://www.airbnb.fr/calendar/ical/123.ics?s=secret',
    ]);
});

it('note chaque lecture dans le journal du calendrier, avec son bilan', function () {
    Http::fake(['www.airbnb.fr/*' => Http::response(icalFixture('airbnb'), 200, ['ETag' => '"v1"'])]);

    $log = app(IcalImportService::class)->sync($this->feed);

    expect($log)->trigger->toBe(IcalSyncTrigger::Scheduled)->status->toBe(IcalSyncStatus::Success)
        ->created_count->toBe(2)->error->toBeNull()
        ->and(IcalSyncLog::count())->toBe(1)
        ->and($this->feed->fresh()->last_succeeded_at?->toDateTimeString())->toBe('2026-11-13 10:00:00');
});

it('garde la date du dernier import réussi pendant une panne', function () {
    $this->feed->update(['last_succeeded_at' => '2026-11-13 09:00:00']);
    Http::fake(['www.airbnb.fr/*' => Http::response('', 500)]);

    app(IcalImportService::class)->sync($this->feed);

    expect($this->feed->fresh())
        ->last_status->toBe(IcalSyncStatus::Failed)
        ->last_succeeded_at->toDateTimeString()->toBe('2026-11-13 09:00:00')
        ->last_synced_at->toDateTimeString()->toBe('2026-11-13 10:00:00');
});

it('alerte l’hôte une seule fois, au 3e échec consécutif', function () {
    Http::fake(['www.airbnb.fr/*' => Http::response('', 503)]);
    $service = app(IcalImportService::class);

    foreach (range(1, 5) as $attempt) {
        $service->sync($this->feed->fresh());
    }

    expect($this->feed->fresh()->consecutive_failures)->toBe(5);
    Mail::assertQueuedCount(1);
    Mail::assertQueued(IcalFeedFailingAlert::class, fn (IcalFeedFailingAlert $mail) => $mail->hasTo('hote@example.com')
        && str_contains($mail->render(), '503')
        && $mail->feed->consecutive_failures === 3);
});

it('efface le journal au bout de 30 jours', function () {
    IcalSyncLog::query()->forceCreate(['ical_feed_id' => $this->feed->id, 'trigger' => 'scheduled', 'status' => 'success', 'created_at' => now()->subDays(31)]);
    IcalSyncLog::query()->forceCreate(['ical_feed_id' => $this->feed->id, 'trigger' => 'scheduled', 'status' => 'success', 'created_at' => now()->subDays(29)]);

    $this->artisan('model:prune', ['--model' => [IcalSyncLog::class]])->assertSuccessful();

    expect(IcalSyncLog::count())->toBe(1);
    $this->artisan('schedule:list')->expectsOutputToContain('model:prune');
});

describe('back-office', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->create()));

    it('synchronise maintenant, en une seule tentative, et renvoie le bilan', function () {
        Http::fake(['www.airbnb.fr/*' => Http::sequence()->push('', 503)->push(icalFixture('airbnb'))]);

        // 201 : chaque lecture ajoute une ligne au journal.
        $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/sync")
            ->assertCreated()
            ->assertJsonPath('data.trigger', 'manual')
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error', fn (string $error) => str_contains($error, '503'));
        Http::assertSentCount(1);

        $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/sync")
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.created', 2);
    });

    it('refuse de synchroniser un calendrier sans URL d’import', function () {
        $this->feed->update(['import_url' => null]);

        $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/sync")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'ical_feed_not_importable');
    });

    it('affiche les 20 dernières synchronisations, de la plus récente à la plus ancienne', function () {
        foreach (range(1, 25) as $minute) {
            IcalSyncLog::query()->forceCreate([
                'ical_feed_id' => $this->feed->id, 'trigger' => 'scheduled', 'status' => 'unchanged',
                'created_at' => now()->subMinutes(30 - $minute),
            ]);
        }

        $this->getJson("/api/admin/ical-feeds/{$this->feed->id}/logs")
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.synced_at', now()->subMinutes(5)->toIso8601String());
    });

    it('donne l’état du calendrier : dernière lecture, dernier succès, erreur', function () {
        $this->feed->update([
            'last_synced_at' => now(), 'last_succeeded_at' => now()->subHour(),
            'last_status' => IcalSyncStatus::Failed, 'last_error' => 'Erreur', 'consecutive_failures' => 2,
        ]);

        $this->getJson('/api/admin/ical-feeds')
            ->assertJsonPath('data.0.last_status', 'failed')
            ->assertJsonPath('data.0.last_error', 'Erreur')
            ->assertJsonPath('data.0.consecutive_failures', 2)
            ->assertJsonPath('data.0.last_succeeded_at', now()->subHour()->toIso8601String());
    });

    it('réserve le journal et la synchronisation aux administrateurs', function () {
        $this->actingAs(User::factory()->housekeeping()->create());

        $this->getJson("/api/admin/ical-feeds/{$this->feed->id}/logs")->assertForbidden();
        $this->postJson("/api/admin/ical-feeds/{$this->feed->id}/sync")->assertForbidden();
    });
});
