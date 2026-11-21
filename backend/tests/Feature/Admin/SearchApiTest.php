<?php

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->actingAs(User::factory()->manager()->create());
    $this->cocon = Apartment::factory()->create(['name' => ['fr' => 'Le Cocon', 'en' => 'Cocoon']]);
    Apartment::factory()->create(['name' => ['fr' => 'L’élégance Mancelle', 'en' => 'Elegance']]);
});

it('trouve les réservations par référence, nom ou e-mail, les plus récentes d’abord', function () {
    $old = directBooking($this->cocon, '2026-10-01', '2026-10-03');
    $old->update(['guest_name' => 'Camille Martin', 'guest_email' => 'camille@example.com']);
    $recent = directBooking($this->cocon, '2026-12-01', '2026-12-03');
    $recent->update(['guest_name' => 'Camille Roux']);

    $this->getJson('/api/admin/search?q=camille')
        ->assertOk()
        ->assertJsonPath('data.bookings.0.reference', $recent->reference)
        ->assertJsonPath('data.bookings.1.guest_name', 'Camille Martin')
        ->assertJsonPath('data.bookings.0.apartment', 'Le Cocon');
    $this->getJson('/api/admin/search?q='.strtolower($old->reference))->assertJsonCount(1, 'data.bookings');
    $this->getJson('/api/admin/search?q=camille@example')->assertJsonCount(1, 'data.bookings');
});

it('trouve les logements sans tenir compte des accents ni de la casse', function () {
    $this->getJson('/api/admin/search?q=ELEGANCE')
        ->assertJsonPath('data.apartments.0.name', 'L’élégance Mancelle')
        ->assertJsonCount(0, 'data.bookings');
});

it('neutralise les jokers SQL et refuse une recherche trop courte', function () {
    directBooking($this->cocon, '2026-12-01', '2026-12-03');

    $this->getJson('/api/admin/search?q=%25%25')->assertJsonCount(0, 'data.bookings');
    $this->getJson('/api/admin/search?q=a')->assertJsonValidationErrors('q');
    $this->getJson('/api/admin/search?q[]=camille')->assertJsonValidationErrors('q');
    $this->actingAs(User::factory()->housekeeping()->create())->getJson('/api/admin/search?q=camille')->assertForbidden();
});
