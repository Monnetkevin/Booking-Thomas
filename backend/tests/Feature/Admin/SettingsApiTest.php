<?php

use App\Enums\AuditAction;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\RatePeriod;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->actingAs(User::factory()->create());
});

function policies(array $overrides = []): array
{
    return array_merge([
        'flexible' => [['days_before' => 1, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 0]],
        'moderate' => [['days_before' => 5, 'refund_percent' => 100], ['days_before' => 0, 'refund_percent' => 50]],
        'strict' => [['days_before' => 14, 'refund_percent' => 100], ['days_before' => 7, 'refund_percent' => 50], ['days_before' => 0, 'refund_percent' => 0]],
    ], $overrides);
}

it('donne les réglages actuels', function () {
    $this->getJson('/api/admin/settings')->assertOk()
        ->assertJsonPath('data.min_notice_days', 1)
        ->assertJsonPath('data.horizon_months', 12)
        ->assertJsonPath('data.tourist_tax.mode', 'per_adult_night')
        ->assertJsonPath('data.cancellation_policies.strict.0', ['days_before' => 14, 'refund_percent' => 100])
        ->assertJsonCount(1, 'data.notification_emails');
});

it('enregistre les réglages modifiés, paliers triés, et ne journalise que ce qui change', function () {
    $this->putJson('/api/admin/settings', [
        'min_notice_days' => 2,
        'horizon_months' => 12,
        'tourist_tax' => ['mode' => 'per_adult_night', 'amount' => 130, 'percentage' => 5, 'cap_per_adult_night' => 400],
        'cancellation_policies' => policies(['moderate' => [['days_before' => 0, 'refund_percent' => 50], ['days_before' => 7, 'refund_percent' => 100]]]),
        'notification_emails' => [' Hote@Example.com ', 'associe@example.com', 'hote@example.com'],
    ])->assertOk()
        ->assertJsonPath('data.min_notice_days', 2)
        ->assertJsonPath('data.tourist_tax', ['mode' => 'per_adult_night', 'amount' => 130, 'percentage' => null, 'cap_per_adult_night' => null])
        ->assertJsonPath('data.cancellation_policies.moderate.0.days_before', 7)
        ->assertJsonPath('data.notification_emails', ['hote@example.com', 'associe@example.com']);

    expect(app(SettingRepositoryInterface::class)->get('booking.min_notice_days'))->toBe(2);
    $log = AuditLog::sole();
    expect($log->action)->toBe(AuditAction::SettingsUpdated)
        ->and(array_keys($log->after))->toEqualCanonicalizing(['min_notice_days', 'tourist_tax', 'cancellation_policies', 'notification_emails'])
        ->and($log->before['min_notice_days'])->toBe(1);
});

it('refuse des valeurs hors limites', function () {
    $this->putJson('/api/admin/settings', [
        'min_notice_days' => 90,
        'tourist_tax' => ['mode' => 'percentage', 'percentage' => 25],
        'cancellation_policies' => policies(['strict' => [['days_before' => 3, 'refund_percent' => 120], ['days_before' => 3, 'refund_percent' => 0]]]),
        'notification_emails' => ['pas-un-email'],
    ])->assertJsonValidationErrors([
        'min_notice_days', 'tourist_tax.percentage', 'cancellation_policies.strict.0.refund_percent',
        'cancellation_policies.strict.0.days_before', 'notification_emails.0',
    ]);
});

it('exige les trois barèmes ensemble et au moins une adresse', function () {
    $this->putJson('/api/admin/settings', ['cancellation_policies' => ['flexible' => policies()['flexible']], 'notification_emails' => []])
        ->assertJsonValidationErrors(['cancellation_policies.moderate', 'cancellation_policies.strict', 'notification_emails']);
});

it('applique tout de suite la taxe de séjour au devis', function () {
    $apartment = Apartment::factory()->create(['status' => 'published', 'min_stay' => 1, 'turnover_days' => 0, 'capacity' => 4, 'cleaning_fee' => 0]);
    RatePeriod::factory()->for($apartment)->create(['starts_on' => now()->toDateString(), 'ends_on' => now()->addYear()->toDateString(), 'nightly_price' => 10000, 'min_stay' => 1]);
    $stay = ['apartment' => $apartment->slug, 'arrival' => now()->addDays(10)->toDateString(), 'departure' => now()->addDays(12)->toDateString(), 'adults' => 2, 'children' => 0];
    $this->postJson('/api/quotes', $stay)->assertJsonPath('data.tourist_tax', 0);

    $this->putJson('/api/admin/settings', ['tourist_tax' => ['mode' => 'per_adult_night', 'amount' => 100]])->assertOk();

    $this->postJson('/api/quotes', $stay)->assertJsonPath('data.tourist_tax', 400);
});

it('réserve les réglages à l’administrateur', function () {
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson('/api/admin/settings')->assertForbidden();
    $this->putJson('/api/admin/settings', ['min_notice_days' => 2])->assertForbidden();
});
