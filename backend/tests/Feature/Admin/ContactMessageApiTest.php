<?php

use App\Models\Apartment;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->create());
});

function contactMessage(array $attributes = []): ContactMessage
{
    return ContactMessage::query()->create(array_merge([
        'name' => 'Léa Martin', 'email' => 'lea@example.com', 'phone' => null,
        'message' => 'Bonjour, le logement est-il libre ?', 'locale' => 'fr',
    ], $attributes));
}

it('liste les messages, les plus récents d’abord, avec le logement et le nombre de non lus', function () {
    $apartment = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]);
    contactMessage(['name' => 'Ancien', 'read_at' => now()]);
    contactMessage(['name' => 'Récent', 'apartment_id' => $apartment->id]);

    $this->getJson('/api/admin/messages')->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Récent')
        ->assertJsonPath('data.0.apartment.name', 'Le Cocon')
        ->assertJsonPath('data.0.read', false)
        ->assertJsonPath('meta.unread', 1);
    $this->getJson('/api/admin/messages?unread=1')->assertJsonCount(1, 'data');
    $this->getJson('/api/admin/messages/unread-count')->assertExactJson(['data' => ['count' => 1]]);
});

it('marque un message lu, puis non lu', function () {
    $message = contactMessage();

    $this->patchJson("/api/admin/messages/{$message->id}", ['read' => true])->assertOk()->assertJsonPath('data.read', true);
    expect($message->fresh()->read_at)->not->toBeNull();

    $this->patchJson("/api/admin/messages/{$message->id}", ['read' => false])->assertJsonPath('data.read', false);
    $this->patchJson("/api/admin/messages/{$message->id}", [])->assertJsonValidationErrors('read');
});

it('réserve les messages (coordonnées des visiteurs) à l’administrateur', function () {
    $message = contactMessage();
    $this->actingAs(User::factory()->housekeeping()->create());

    $this->getJson('/api/admin/messages')->assertForbidden();
    $this->patchJson("/api/admin/messages/{$message->id}", ['read' => true])->assertForbidden();
});
