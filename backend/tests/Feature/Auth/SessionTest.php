<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Requêtes « venant de la SPA » : Sanctum active alors la session et le cookie.
    config(['sanctum.stateful' => ['localhost:5173']]);
    $this->withHeader('Origin', 'http://localhost:5173');

    $this->admin = User::factory()->create([
        'email' => 'hote@example.com',
        'password' => 'un-mot-de-passe-solide',
    ]);
});

it('connecte un administrateur et renvoie son profil', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'HOTE@example.com',
        'password' => 'un-mot-de-passe-solide',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', 'hote@example.com')
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonMissingPath('data.password');

    $this->assertAuthenticatedAs($this->admin, 'web');
    $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.id', $this->admin->id);
});

it('refuse un mauvais mot de passe sans dire si le compte existe', function (string $email) {
    $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'faux-mot-de-passe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')]);

    $this->assertGuest('web');
})->with(['hote@example.com', 'inconnu@example.com']);

it('refuse un compte désactivé', function () {
    $this->admin->update(['is_active' => false]);

    $this->postJson('/api/auth/login', ['email' => 'hote@example.com', 'password' => 'un-mot-de-passe-solide'])
        ->assertUnprocessable();
});

it('bloque après 5 tentatives échouées', function () {
    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/auth/login', ['email' => 'hote@example.com', 'password' => 'faux']);
    }

    $this->postJson('/api/auth/login', ['email' => 'hote@example.com', 'password' => 'un-mot-de-passe-solide'])
        ->assertStatus(429);
});

it('déconnecte et invalide la session', function () {
    $this->actingAs($this->admin, 'web');

    $this->postJson('/api/auth/logout')->assertNoContent();

    $this->assertGuest('web');
});

it('répond 401 aux visiteurs non connectés', function () {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});

describe('rôles', function () {
    beforeEach(function () {
        Route::middleware(['api', 'auth:sanctum', 'role:admin'])->get('/api/admin/_test', fn () => 'ok');
    });

    it('ouvre le back-office aux administrateurs', function () {
        $this->actingAs($this->admin)->getJson('/api/admin/_test')->assertOk();
    });

    it('le ferme au rôle ménage (403)', function () {
        $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/_test')->assertForbidden();
    });

    it('déconnecte un compte désactivé pendant sa session (401)', function () {
        $this->admin->update(['is_active' => false]);

        $this->actingAs($this->admin)->getJson('/api/admin/_test')->assertUnauthorized();
    });

    it('donne au rôle ménage accès à son profil', function () {
        $this->actingAs(User::factory()->housekeeping()->create())
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'housekeeping');
    });
});
