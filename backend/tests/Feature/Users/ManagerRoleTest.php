<?php

use App\Enums\ApartmentStatus;
use App\Enums\AuditAction;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Mail\AccountPasswordLink;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\IcalFeed;
use App\Models\Notification;
use App\Models\RatePeriod;
use App\Models\User;
use App\Notifications\Host\IcalFeedFailingNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->create();
    $this->manager = User::factory()->manager()->create();
    $this->apartment = Apartment::factory()->create();
});

it('ouvre au gestionnaire l’activité du back-office', function () {
    $this->actingAs($this->manager);

    foreach (['/api/admin/dashboard', '/api/admin/planning', '/api/admin/bookings', '/api/admin/statistics', '/api/admin/apartments',
        "/api/admin/apartments/{$this->apartment->id}", '/api/admin/ical-feeds', '/api/admin/messages',
        '/api/admin/notifications', '/api/auth/me'] as $url) {
        $this->getJson($url)->assertOk();
    }
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/blocks", ['starts_on' => now()->addDays(10)->toDateString(), 'ends_on' => now()->addDays(12)->toDateString()])
        ->assertCreated();
});

it('refuse au gestionnaire les réglages, les comptes et les tarifs', function () {
    $period = RatePeriod::factory()->for($this->apartment)->create();
    $this->actingAs($this->manager);

    $this->getJson('/api/admin/settings')->assertForbidden();
    // Le journal contient réglages, tarifs, comptes et adresses IP : administrateur seulement.
    $this->getJson('/api/admin/audit-logs')->assertForbidden();
    $this->putJson('/api/admin/settings', [])->assertForbidden();
    $this->getJson('/api/admin/users')->assertForbidden();
    $this->postJson('/api/admin/users', ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
    $this->postJson("/api/admin/users/{$this->admin->id}/password-reset")->assertForbidden();
    $this->getJson("/api/admin/apartments/{$this->apartment->id}/rate-periods")->assertForbidden();
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/rate-periods", [])->assertForbidden();
    $this->putJson("/api/admin/rate-periods/{$period->id}", [])->assertForbidden();
    $this->deleteJson("/api/admin/rate-periods/{$period->id}")->assertForbidden();
});

it('refuse l’activité du back-office au ménage et à un gestionnaire désactivé', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/bookings')->assertForbidden();
    // Requête de la SPA (session) : le compte désactivé est déconnecté.
    config(['sanctum.stateful' => ['localhost:5173']]);
    $this->withHeader('Origin', 'http://localhost:5173')
        ->actingAs(User::factory()->manager()->inactive()->create())
        ->getJson('/api/admin/bookings')
        ->assertUnauthorized();
});

it('invite un gestionnaire, change un rôle, jamais celui d’un administrateur', function () {
    $this->actingAs($this->admin);

    $this->postJson('/api/admin/users', ['name' => 'Gaëlle', 'email' => 'gestion@example.com', 'role' => 'manager'])
        ->assertCreated()->assertJsonPath('data.role', 'manager');
    Mail::assertQueued(AccountPasswordLink::class, fn ($mail) => $mail->hasTo('gestion@example.com'));
    $this->postJson('/api/admin/users', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'admin'])->assertJsonValidationErrors('role');

    $housekeeper = User::factory()->housekeeping()->create();
    $this->patchJson("/api/admin/users/{$housekeeper->id}", ['role' => 'manager'])->assertOk()->assertJsonPath('data.role', 'manager');
    expect(AuditLog::query()->where('action', AuditAction::UserUpdated->value)->sole())
        ->before->toBe(['role' => 'housekeeping'])
        ->after->toBe(['role' => 'manager']);

    $other = User::factory()->create();
    $this->patchJson("/api/admin/users/{$other->id}", ['role' => 'manager'])->assertUnprocessable()->assertJsonPath('code', 'admin_role_fixed');
    expect($other->fresh()->role)->toBe(UserRole::Admin);
});

it('envoie au gestionnaire les mêmes alertes qu’à l’administrateur', function () {
    $feed = IcalFeed::factory()->for($this->apartment)->create(['consecutive_failures' => 3]);
    User::factory()->housekeeping()->create();

    app(NotificationDispatcher::class)->dispatch(new IcalFeedFailingNotification($feed));

    expect(Notification::query()->pluck('notifiable_id')->sort()->values()->all())
        ->toBe(collect([$this->admin->id, $this->manager->id])->sort()->values()->all())
        ->and(NotificationType::for(UserRole::Manager))->toBe(NotificationType::for(UserRole::Admin));
});

it('note la dernière connexion', function () {
    config(['sanctum.stateful' => ['localhost:5173']]);
    $user = User::factory()->manager()->create(['password' => 'secret-de-test-123']);

    $this->withHeader('Origin', 'http://localhost:5173')
        ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret-de-test-123'])->assertSuccessful();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('donne la dernière connexion dans la liste des comptes', function () {
    $this->manager->forceFill(['last_login_at' => '2026-11-19 08:30:00'])->save();

    $accounts = collect($this->actingAs($this->admin)->getJson('/api/admin/users')->json('data'))->keyBy('id');

    expect($accounts[$this->manager->id]['last_login_at'])->toStartWith('2026-11-19T08:30:00')
        ->and($accounts[$this->admin->id]['last_login_at'])->toBeNull();
});

it('laisse le gestionnaire voir les prix sans pouvoir les changer, ni à la création ni ensuite', function () {
    $this->apartment->update(['cleaning_fee' => 4000]);
    $fields = [
        'name' => ['fr' => 'Le Cocon'], 'address' => '1 rue de la Paix', 'postal_code' => '72000', 'city' => 'Le Mans',
        'capacity' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1, 'check_in_time' => '16:00', 'check_out_time' => '11:00',
        'min_stay' => 2, 'max_stay' => 30, 'turnover_days' => 0, 'cancellation_policy' => 'moderate',
    ];
    $this->actingAs($this->manager);

    $this->getJson('/api/admin/apartments')->assertJsonPath('data.0.cleaning_fee', 4000);
    $this->putJson("/api/admin/apartments/{$this->apartment->id}", [...$fields, 'cleaning_fee' => 0])->assertOk();
    expect($this->apartment->fresh()->cleaning_fee)->toBe(4000);

    $this->getJson('/api/auth/me')->assertJsonPath('data.can_manage_pricing', false);
    $created = $this->postJson('/api/admin/apartments', [...$fields, 'name' => ['fr' => 'Nouveau'], 'cleaning_fee' => 50000])
        ->assertCreated()
        ->assertJsonPath('data.cleaning_fee', 0)
        ->json('data.id');
    expect(Apartment::find($created)->cleaning_fee)->toBe(0);
    $this->postJson('/api/admin/apartments', [...$fields, 'name' => ['fr' => 'Sans prix']])->assertCreated();

    $this->actingAs($this->admin)->getJson('/api/auth/me')->assertJsonPath('data.can_manage_pricing', true);
    $this->putJson("/api/admin/apartments/{$this->apartment->id}", [...$fields, 'cleaning_fee' => 5000])->assertOk();
    expect($this->apartment->fresh()->cleaning_fee)->toBe(5000);
});

it('efface les notifications de l’ancien rôle quand un gestionnaire passe au ménage', function () {
    $feed = IcalFeed::factory()->for($this->apartment)->create(['consecutive_failures' => 3]);
    app(NotificationDispatcher::class)->dispatch(new IcalFeedFailingNotification($feed));
    expect($this->manager->notifications()->count())->toBe(1);

    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$this->manager->id}", ['role' => 'housekeeping'])->assertOk();

    expect($this->manager->notifications()->count())->toBe(0)
        ->and($this->admin->notifications()->count())->toBe(1);
    $this->actingAs($this->manager->fresh())->getJson('/api/admin/notifications')->assertJsonCount(0, 'data');
});

it('laisse le gestionnaire voir les prix (aperçu, brouillons compris) mais pas fixer un prix libre', function () {
    $draft = Apartment::factory()->create(['status' => ApartmentStatus::Draft, 'capacity' => 4, 'min_stay' => 1]);
    RatePeriod::factory()->for($draft)->create(['starts_on' => now()->toDateString(), 'ends_on' => now()->addMonths(2)->toDateString(), 'nightly_price' => 9000, 'min_stay' => 1]);
    $stay = ['arrival' => now()->addDays(10)->toDateString(), 'departure' => now()->addDays(12)->toDateString(), 'adults' => 2];
    $booking = [...$stay, 'apartment_id' => $draft->id, 'guest_name' => 'Paul', 'paid' => false, 'send_confirmation' => false];
    $this->actingAs($this->manager);

    $this->postJson("/api/admin/apartments/{$draft->id}/quote-preview", $stay)->assertOk();
    $this->postJson('/api/admin/bookings', [...$booking, 'total_amount' => 15000])->assertForbidden();
    $this->postJson('/api/admin/bookings', $booking)->assertCreated();
    $this->actingAs($this->admin)->postJson('/api/admin/bookings', [...$booking, 'arrival' => now()->addDays(20)->toDateString(), 'departure' => now()->addDays(22)->toDateString(), 'total_amount' => 15000])->assertCreated();
});
