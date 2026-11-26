<?php

use App\Models\User;

it('donne les préférences de l’hôte : chaque type de son rôle, e-mail et réglable ou non', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/admin/me/notification-preferences')
        ->assertOk()
        ->assertJsonPath('data.types.0', ['type' => 'ical_conflict', 'email' => true, 'editable' => false])
        ->assertJsonPath('data.types.1', ['type' => 'ical_feed_failing', 'email' => true, 'editable' => true])
        ->assertJsonPath('data.types.3', ['type' => 'ical_feed_recovered', 'email' => false, 'editable' => true])
        ->assertJsonPath('data.types.4', ['type' => 'booking_confirmed', 'email' => false, 'editable' => false])
        ->assertJsonPath('data.types.6', ['type' => 'review_submitted', 'email' => false, 'editable' => true])
        ->assertJsonCount(7, 'data.types');
});

it('donne au ménage ses seuls types et son récapitulatif', function () {
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/me/notification-preferences')
        ->assertOk()
        ->assertJsonPath('data.types', [
            ['type' => 'stay_changed', 'email' => true, 'editable' => true],
            ['type' => 'daily_summary', 'email' => true, 'editable' => true],
        ])
        ->assertJsonPath('data.summary_enabled', true)
        ->assertJsonPath('data.summary_hour', 18);
});

it('enregistre les choix du compte ; un e-mail obligatoire reste envoyé', function () {
    $host = User::factory()->create();

    $this->actingAs($host)->putJson('/api/admin/me/notification-preferences', [
        'email' => ['ical_feed_failing' => false, 'ical_feed_recovered' => true, 'ical_conflict' => false],
    ])->assertOk()
        ->assertJsonPath('data.types.0.email', true)
        ->assertJsonPath('data.types.1.email', false)
        ->assertJsonPath('data.types.3.email', true);

    expect($host->fresh()->notification_preferences['email'])->toBe(['ical_feed_failing' => false, 'ical_feed_recovered' => true]);
});

it('règle le récapitulatif du ménage entre 6 h et 22 h', function () {
    $cleaner = User::factory()->housekeeping()->create();
    $this->actingAs($cleaner);

    $this->putJson('/api/admin/me/notification-preferences', ['summary_enabled' => false, 'summary_hour' => 7])
        ->assertOk()->assertJsonPath('data.summary_enabled', false)->assertJsonPath('data.summary_hour', 7);
    $this->putJson('/api/admin/me/notification-preferences', ['summary_hour' => 23])->assertJsonValidationErrors('summary_hour');
});

it('refuse les types d’un autre rôle', function () {
    $this->actingAs(User::factory()->housekeeping()->create())
        ->putJson('/api/admin/me/notification-preferences', ['email' => ['ical_feed_failing' => false]])
        ->assertJsonValidationErrors('email');
});
