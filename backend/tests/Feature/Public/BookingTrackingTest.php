<?php

use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\Booking;
use App\Services\Booking\BookingLinks;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    Storage::fake('public');
    config(['booking.frontend_url' => 'https://exemple.fr']);

    $this->apartment = Apartment::factory()->create([
        'slug' => 'le-cocon', 'name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon'],
        'address' => '12 bis rue de la Paix', 'check_in_time' => '16:00:00', 'check_out_time' => '10:00:00',
    ]);
    $this->booking = Booking::factory()->for($this->apartment)->create([
        'reference' => 'BT-ABCD2345', 'status' => BookingStatus::Pending, 'locale' => 'fr',
        'arrival' => '2026-11-17', 'departure' => '2026-11-20', 'adults' => 2, 'children' => 1,
        'expires_at' => now()->addMinutes(30), 'total_amount' => 28600,
    ]);
});

function trackingPath(Booking $booking): string
{
    return "/api/bookings/{$booking->reference}?signature=".app(BookingLinks::class)->signature('bookings.show', $booking);
}

it('donne au voyageur un lien de suivi signé vers la page du site, dans sa langue', function () {
    $url = app(BookingLinks::class)->trackingUrl($this->booking);
    expect($url)->toStartWith('https://exemple.fr/reservation/BT-ABCD2345?signature=');

    $this->booking->locale = 'en';
    expect(app(BookingLinks::class)->trackingUrl($this->booking))->toStartWith('https://exemple.fr/en/reservation/BT-ABCD2345?signature=');
});

it('affiche la réservation avec un lien signé valide', function () {
    $this->getJson(trackingPath($this->booking), ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJsonPath('data.reference', 'BT-ABCD2345')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.expires_at', now()->addMinutes(30)->toIso8601String())
        ->assertJsonPath('data.stay.nights', 3)
        ->assertJsonPath('data.stay.children', 1)
        ->assertJsonPath('data.apartment.name', 'The Cocoon')
        ->assertJsonPath('data.apartment.check_in_time', '16:00')
        ->assertJsonPath('data.price.total', 28600);
});

it('ne donne l’adresse exacte qu’une fois la réservation confirmée', function () {
    $this->getJson(trackingPath($this->booking))->assertJsonPath('data.apartment.address', 'rue de la Paix');

    $this->booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()]);
    $this->getJson(trackingPath($this->booking))
        ->assertJsonPath('data.apartment.address', '12 bis rue de la Paix')
        ->assertJsonPath('data.expires_at', null);
});

it('refuse un lien sans signature, falsifié ou celui d’une autre réservation', function () {
    $other = Booking::factory()->for($this->apartment)->create(['reference' => 'BT-ZZZZ9999']);

    $this->getJson('/api/bookings/BT-ABCD2345')->assertForbidden();
    $this->getJson('/api/bookings/BT-ABCD2345?signature=faux')->assertForbidden();
    $this->getJson('/api/bookings/BT-ZZZZ9999?signature='.app(BookingLinks::class)->signature('bookings.show', $this->booking))
        ->assertForbidden();
    $this->getJson(trackingPath($other))->assertOk();
});
