<?php

use App\Enums\AuditAction;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\RatePeriod;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    Mail::fake();
    $this->admin = User::factory()->create(['name' => 'Thomas']);
    $this->actingAs($this->admin);
    $this->apartment = Apartment::factory()->create();
});

it('journalise la création, la modification et la suppression d’un tarif, avec l’auteur et l’IP', function () {
    $this->postJson("/api/admin/apartments/{$this->apartment->id}/rate-periods", [
        'label' => 'Hiver', 'starts_on' => '2026-12-01', 'ends_on' => '2027-01-04', 'nightly_price' => 9500, 'min_stay' => 3,
    ])->assertCreated();
    $period = RatePeriod::sole();
    $this->putJson("/api/admin/rate-periods/{$period->id}", [
        'label' => 'Hiver', 'starts_on' => '2026-12-01', 'ends_on' => '2027-01-04', 'nightly_price' => 11000, 'min_stay' => 3,
    ])->assertOk();
    $this->deleteJson("/api/admin/rate-periods/{$period->id}")->assertNoContent();

    $logs = AuditLog::query()->orderBy('id')->get();
    expect($logs->pluck('action')->all())->toBe([AuditAction::RatePeriodCreated, AuditAction::RatePeriodUpdated, AuditAction::RatePeriodDeleted])
        ->and($logs[1]->before['nightly_price'])->toBe(9500)
        ->and($logs[1]->after['nightly_price'])->toBe(11000)
        ->and($logs[2]->subject_type)->toBe('RatePeriod')
        ->and($logs[2]->subject_id)->toBe((string) $period->id)
        ->and($logs->every(fn (AuditLog $log) => $log->user_id === $this->admin->id && $log->ip === '127.0.0.1'))->toBeTrue();
});

it('journalise la suppression d’un logement et les changements de comptes, sans mot de passe', function () {
    $this->deleteJson("/api/admin/apartments/{$this->apartment->id}")->assertNoContent();
    $this->postJson('/api/admin/users', ['name' => 'Alice', 'email' => 'alice@example.com'])->assertCreated();
    $alice = User::query()->where('email', 'alice@example.com')->sole();
    $this->postJson("/api/admin/users/{$alice->id}/password-reset")->assertNoContent();
    $this->patchJson("/api/admin/users/{$alice->id}", ['is_active' => false])->assertOk();

    expect(AuditLog::query()->orderBy('id')->pluck('action')->all())->toBe([
        AuditAction::ApartmentDeleted, AuditAction::UserInvited, AuditAction::UserPasswordLinkSent, AuditAction::UserUpdated,
    ])
        ->and(AuditLog::query()->where('action', 'user.updated')->sole()->only('before', 'after'))
        ->toBe(['before' => ['is_active' => true], 'after' => ['is_active' => false]])
        ->and(AuditLog::query()->get(['before', 'after'])->toJson())->not->toContain('"password"');
});

it('montre le journal à l’administrateur, filtré par action, jamais au ménage', function () {
    $this->deleteJson("/api/admin/apartments/{$this->apartment->id}");
    $this->postJson('/api/admin/users', ['name' => 'Alice', 'email' => 'alice@example.com']);

    $this->getJson('/api/admin/audit-logs')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.action', 'user.invited')
        ->assertJsonPath('data.0.user', 'Thomas');
    $this->getJson('/api/admin/audit-logs?action=apartment.deleted')->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/audit-logs?action=inconnue')->assertJsonValidationErrors('action');

    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/audit-logs')->assertForbidden();
});

it('n’offre aucune route pour modifier ou effacer le journal, et le purge au bout d’un an', function () {
    $this->postJson('/api/admin/users', ['name' => 'Alice', 'email' => 'alice@example.com']);
    $log = AuditLog::sole();

    $this->deleteJson("/api/admin/audit-logs/{$log->id}")->assertNotFound();
    $this->putJson("/api/admin/audit-logs/{$log->id}", [])->assertNotFound();

    AuditLog::query()->forceCreate(['action' => 'user.invited', 'created_at' => now()->subMonths(13)]);
    $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();
    expect(AuditLog::count())->toBe(1);
});
