<?php

use App\Mail\AccountPasswordLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    // Requêtes de la SPA : session par cookie (Sanctum).
    config(['sanctum.stateful' => ['localhost:5173']]);
    $this->withHeader('Origin', 'http://localhost:5173');
    Mail::fake();
    config(['booking.frontend_url' => 'https://exemple.fr']);
    $this->admin = User::factory()->create(['name' => 'Thomas']);
});

/** Jeton du lien envoyé par e-mail, lu dans l'adresse du bouton. */
function tokenFromLink(AccountPasswordLink $mail): string
{
    preg_match('/token=([^&"]+)/', $mail->render(), $matches);

    return urldecode($matches[1]);
}

it('liste les comptes du back-office, administrateurs d’abord', function () {
    User::factory()->housekeeping()->create(['name' => 'Alice']);

    $this->actingAs($this->admin)->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Thomas')
        ->assertJsonPath('data.0.is_self', true)
        ->assertJsonPath('data.1', fn (array $user) => $user['name'] === 'Alice' && $user['role'] === 'housekeeping' && $user['is_self'] === false);
});

it('invite un compte ménage par e-mail, avec un lien pour choisir son mot de passe', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/users', ['name' => 'Alice', 'email' => ' Alice@Example.com '])
        ->assertCreated()
        ->assertJsonPath('data.role', 'housekeeping')
        ->assertJsonPath('data.email', 'alice@example.com')
        ->assertJsonPath('data.is_active', true);

    Mail::assertQueued(AccountPasswordLink::class, fn (AccountPasswordLink $mail) => $mail->hasTo('alice@example.com')
        && $mail->invitation
        && str_contains($mail->render(), 'https://exemple.fr/admin/mot-de-passe?token='));
});

it('refuse une adresse déjà utilisée, sans tenir compte de la casse', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    $this->actingAs($this->admin)->postJson('/api/admin/users', ['name' => 'Alice', 'email' => 'ALICE@example.com'])
        ->assertJsonValidationErrors('email');
});

it('laisse le compte ménage choisir son mot de passe une seule fois, puis se connecter', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/users', ['name' => 'Alice', 'email' => 'alice@example.com']);
    $token = tokenFromLink(Mail::queued(AccountPasswordLink::class)->first());
    auth()->guard('web')->logout();

    $this->postJson('/api/auth/password', ['email' => 'alice@example.com', 'token' => $token, 'password' => 'Menage-2026-ok', 'password_confirmation' => 'Menage-2026-ok'])
        ->assertNoContent();
    $this->postJson('/api/auth/password', ['email' => 'alice@example.com', 'token' => $token, 'password' => 'Autre-2026-mdp', 'password_confirmation' => 'Autre-2026-mdp'])
        ->assertJsonValidationErrors('token');

    $this->postJson('/api/auth/login', ['email' => 'alice@example.com', 'password' => 'Menage-2026-ok'])
        ->assertOk()->assertJsonPath('data.role', 'housekeeping');
});

it('refuse un lien inconnu, expiré, ou d’un compte désactivé', function () {
    $cleaner = User::factory()->housekeeping()->create(['email' => 'alice@example.com']);
    $this->actingAs($this->admin)->postJson("/api/admin/users/{$cleaner->id}/password-reset")->assertNoContent();
    $token = tokenFromLink(Mail::queued(AccountPasswordLink::class)->first());
    auth()->guard('web')->logout();
    $payload = ['email' => 'alice@example.com', 'password' => 'Menage-2026-ok', 'password_confirmation' => 'Menage-2026-ok'];

    $this->postJson('/api/auth/password', [...$payload, 'token' => 'faux'])->assertJsonValidationErrors('token');

    $this->travel(25)->hours();
    $this->postJson('/api/auth/password', [...$payload, 'token' => $token])->assertJsonValidationErrors('token');
    $this->travelBack();

    $cleaner->update(['is_active' => false]);
    $this->postJson('/api/auth/password', [...$payload, 'token' => $token])->assertJsonValidationErrors('token');
});

it('exige un mot de passe d’au moins 12 caractères, avec lettres et chiffres', function () {
    $this->postJson('/api/auth/password', ['email' => 'a@example.com', 'token' => 'x', 'password' => 'court', 'password_confirmation' => 'court'])
        ->assertJsonValidationErrors('password');
});

it('active ou désactive un compte ménage ; un compte désactivé perd aussitôt l’accès', function () {
    $cleaner = User::factory()->housekeeping()->create();

    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$cleaner->id}", ['is_active' => false])
        ->assertOk()->assertJsonPath('data.is_active', false);

    $this->actingAs($cleaner->fresh())->getJson('/api/auth/me')->assertUnauthorized();
});

it('refuse de désactiver son propre compte ou le dernier administrateur actif', function () {
    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$this->admin->id}", ['is_active' => false])
        ->assertUnprocessable()->assertJsonPath('code', 'cannot_deactivate_self');

    $other = User::factory()->create();
    $this->actingAs($other)->patchJson("/api/admin/users/{$this->admin->id}", ['is_active' => false])->assertOk();
    $this->actingAs($other)->patchJson("/api/admin/users/{$other->id}", ['is_active' => false])
        ->assertJsonPath('code', 'cannot_deactivate_self');
});

it('réserve la gestion des comptes aux administrateurs', function () {
    $cleaner = User::factory()->housekeeping()->create();

    $this->actingAs($cleaner)->getJson('/api/admin/users')->assertForbidden();
    $this->actingAs($cleaner)->postJson('/api/admin/users', ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
    $this->actingAs($cleaner)->patchJson("/api/admin/users/{$this->admin->id}", ['is_active' => false])->assertForbidden();
});
