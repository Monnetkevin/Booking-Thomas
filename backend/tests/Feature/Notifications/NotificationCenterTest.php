<?php

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\Channels\InAppChannel;
use App\Notifications\Host\IcalConflictNotification;
use App\Notifications\Host\IcalFeedFailingNotification;
use App\Notifications\Host\IcalFeedRecoveredNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

beforeEach(function () {
    Date::setTestNow('2026-11-19 10:00:00');
    $this->host = User::factory()->create();
    $this->cleaner = User::factory()->housekeeping()->create();
});

/** Notification enregistrée pour ce compte. */
function notificationFor(User $user, array $overrides = []): Notification
{
    return app(NotificationRepositoryInterface::class)->record($user, [
        'id' => (string) Str::uuid(),
        'type' => $overrides['type'] ?? NotificationType::ContactMessage,
        'level' => $overrides['level'] ?? NotificationLevel::Info,
        'dedupe_key' => $overrides['dedupe_key'] ?? null,
        'data' => ['title' => $overrides['title'] ?? 'Message de Léa', 'body' => 'Bonjour', 'link' => '/admin', 'details' => []],
    ]);
}

it('liste les notifications du compte connecté, les plus récentes d’abord, et les seules non lues', function () {
    $old = notificationFor($this->host, ['title' => 'Ancienne']);
    Date::setTestNow('2026-11-19 11:00:00');
    notificationFor($this->host, ['title' => 'Récente', 'level' => NotificationLevel::Urgent]);
    notificationFor($this->cleaner, ['title' => 'Pour le ménage']);
    $old->forceFill(['read_at' => now()])->save();

    $this->actingAs($this->host)->getJson('/api/admin/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Récente')
        ->assertJsonPath('data.0.level', 'urgent')
        ->assertJsonPath('data.0.read', false)
        ->assertJsonPath('data.1.read', true);

    $this->getJson('/api/admin/notifications?unread=1')->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/notifications/unread-count')->assertExactJson(['data' => ['count' => 1]]);
});

it('ouvre le centre de notifications au compte ménage, mais pas le reste du back-office', function () {
    notificationFor($this->cleaner);

    $this->actingAs($this->cleaner)->getJson('/api/admin/notifications')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/ical-feeds')->assertForbidden();
    $this->getJson('/api/admin/users')->assertForbidden();
});

it('refuse un compte non connecté ou désactivé', function () {
    // Requêtes de la SPA : session par cookie (Sanctum).
    config(['sanctum.stateful' => ['localhost:5173']]);
    $this->withHeader('Origin', 'http://localhost:5173');
    $this->getJson('/api/admin/notifications')->assertUnauthorized();

    $this->actingAs(User::factory()->inactive()->create())->getJson('/api/admin/notifications')->assertUnauthorized();
});

it('marque lue une notification du compte, jamais celle d’un autre', function () {
    $mine = notificationFor($this->host);
    $theirs = notificationFor($this->cleaner);

    $this->actingAs($this->host)->postJson("/api/admin/notifications/{$mine->id}/read")
        ->assertOk()->assertJsonPath('data.read', true);
    $this->postJson("/api/admin/notifications/{$theirs->id}/read")->assertForbidden();

    expect($theirs->fresh()->read_at)->toBeNull();
});

it('marque tout comme lu pour le seul compte connecté', function () {
    notificationFor($this->host);
    notificationFor($this->host);
    $theirs = notificationFor($this->cleaner);

    $this->actingAs($this->host)->postJson('/api/admin/notifications/read-all')->assertExactJson(['data' => ['marked' => 2]]);

    expect($this->host->notifications()->whereNull('read_at')->count())->toBe(0)
        ->and($theirs->fresh()->read_at)->toBeNull();
});

it('rafraîchit une notification non lue du même objet au lieu de la répéter', function () {
    $first = notificationFor($this->host, ['type' => NotificationType::IcalFeedFailing, 'dedupe_key' => 'feed:1', 'title' => 'Échec']);
    Date::setTestNow('2026-11-19 12:00:00');
    $again = notificationFor($this->host, ['type' => NotificationType::IcalFeedFailing, 'dedupe_key' => 'feed:1', 'title' => 'Échec encore']);

    expect($again->id)->toBe($first->id)
        ->and(Notification::count())->toBe(1)
        ->and($first->fresh()->data['title'])->toBe('Échec encore')
        ->and($first->fresh()->created_at->toDateTimeString())->toBe('2026-11-19 12:00:00');

    // Une fois lue, la suivante est une nouvelle notification.
    $first->forceFill(['read_at' => now()])->save();
    notificationFor($this->host, ['type' => NotificationType::IcalFeedFailing, 'dedupe_key' => 'feed:1']);
    notificationFor($this->host, ['type' => NotificationType::IcalFeedRecovered, 'dedupe_key' => 'feed:1']);

    expect(Notification::count())->toBe(3);
});

it('supprime les notifications lues depuis plus de 90 jours, jamais les non lues', function () {
    notificationFor($this->host)->forceFill(['read_at' => now()->subDays(91)])->save();
    notificationFor($this->host)->forceFill(['read_at' => now()->subDays(89)])->save();
    notificationFor($this->host)->forceFill(['created_at' => now()->subDays(200)])->save();

    $this->artisan('model:prune', ['--model' => [Notification::class]])->assertSuccessful();

    expect(Notification::count())->toBe(2);
    $this->artisan('schedule:list')->expectsOutputToContain('notifications:housekeeping-summary');
});

describe('canaux', function () {
    beforeEach(function () {
        $this->feed = IcalFeed::factory()->create();
    });

    it('envoie les urgentes aussi par e-mail, les informatives dans le back-office seulement', function () {
        expect((new IcalFeedFailingNotification($this->feed))->via($this->host))->toBe([InAppChannel::class, 'mail'])
            ->and((new IcalFeedRecoveredNotification($this->feed))->via($this->host))->toBe([InAppChannel::class]);
    });

    it('rédige la notification d’un calendrier dont le logement a été supprimé', function () {
        $this->feed->apartment->delete();

        expect((new IcalFeedFailingNotification($this->feed->fresh()))->title())->toStartWith('Calendrier Airbnb en échec : ');
    });

    it('respecte l’e-mail coupé par le compte, sauf pour un conflit de dates', function () {
        $this->host->update(['notification_preferences' => ['email' => ['ical_feed_failing' => false, 'ical_conflict' => false]]]);
        $block = AvailabilityBlock::factory()->create();

        expect((new IcalFeedFailingNotification($this->feed))->via($this->host))->toBe([InAppChannel::class])
            ->and((new IcalConflictNotification($block, new Collection))->via($this->host))->toBe([InAppChannel::class, 'mail']);
    });
});
