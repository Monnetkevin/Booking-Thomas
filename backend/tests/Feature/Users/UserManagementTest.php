<?php

use App\Mail\AccountPasswordLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

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

it('ne se laisse pas contourner par un « 0 » à la place de false', function () {
    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$this->admin->id}", ['is_active' => 0])
        ->assertUnprocessable()->assertJsonPath('code', 'cannot_deactivate_self');
    $this->actingAs($this->admin)->patchJson("/api/admin/users/{$this->admin->id}", ['is_active' => '0'])
        ->assertJsonPath('code', 'cannot_deactivate_self');

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

it('ferme les sessions ouvertes et les cookies « se souvenir de moi » au changement de mot de passe', function () {
    $cleaner = User::factory()->housekeeping()->create(['email' => 'alice@example.com', 'password' => 'Ancien-2026-mdp', 'remember_token' => 'ancien']);
    $this->postJson('/api/auth/login', ['email' => 'alice@example.com', 'password' => 'Ancien-2026-mdp'])->assertOk();
    $this->getJson('/api/auth/me')->assertOk();
    $token = Password::broker()->createToken($cleaner);

    $this->postJson('/api/auth/password', ['email' => 'alice@example.com', 'token' => $token, 'password' => 'Nouveau-2026-mdp', 'password_confirmation' => 'Nouveau-2026-mdp'])
        ->assertNoContent();

    // Requête suivante du navigateur : le compte est relu depuis la session (comme en production).
    app('auth')->forgetGuards();
    $this->getJson('/api/auth/me')->assertUnauthorized();
    expect($cleaner->fresh()->remember_token)->not->toBe('ancien');
});

it('n’accepte plus le rôle gestionnaire, ni à l’invitation ni au changement de rôle', function () {
    $this->actingAs($this->admin);
    $housekeeper = User::factory()->housekeeping()->create();

    $this->postJson('/api/admin/users', ['name' => 'Gaëlle', 'email' => 'gestion@example.com', 'role' => 'manager'])->assertJsonValidationErrors('role');
    $this->patchJson("/api/admin/users/{$housekeeper->id}", ['role' => 'manager'])->assertJsonValidationErrors('role');
});

it('fait d’un ancien gestionnaire un compte ménage désactivé, jamais un administrateur', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Ancien', 'email' => 'ancien@example.com', 'password' => 'x', 'role' => 'manager', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

    (require database_path('migrations/2026_11_22_120000_remove_manager_role.php'))->up();

    expect(User::query()->find($id))->role->value->toBe('housekeeping')->is_active->toBeFalse();
});
