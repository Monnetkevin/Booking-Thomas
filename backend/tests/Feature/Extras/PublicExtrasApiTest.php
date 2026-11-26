<?php

use App\Enums\ApartmentStatus;
use App\Enums\ExtraPricing;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Extra;
use App\Models\RatePeriod;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    RateLimiter::clear('bookings');
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 0]);
    $this->apartment = Apartment::factory()->create([
        'slug' => 'le-loft', 'status' => ApartmentStatus::Published, 'capacity' => 4, 'min_stay' => 1, 'max_stay' => 14,
        'cleaning_fee' => 0, 'turnover_days' => 0, 'check_in_time' => '16:00:00',
    ]);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-12-31', 'nightly_price' => 10000, 'weekend_supplement' => null]);
    $this->champagne = Extra::factory()->create(['name' => ['fr' => 'Champagne', 'en' => 'Champagne'], 'price' => 4500, 'pricing' => ExtraPricing::PerUnit, 'max_quantity' => 3, 'lead_time_hours' => 48]);
    $this->breakfast = Extra::factory()->create(['name' => ['fr' => 'Petit-déjeuner', 'en' => 'Breakfast'], 'price' => 1200, 'pricing' => ExtraPricing::PerGuestNight, 'lead_time_hours' => 0]);
    $this->champagne->apartments()->attach($this->apartment);
    $this->breakfast->apartments()->attach($this->apartment);
    Extra::factory()->create(['is_active' => false])->apartments()->attach($this->apartment);
});

it('liste les options encore commandables pour une arrivée, dans la langue du voyageur', function () {
    $this->getJson('/api/apartments/le-loft/extras?arrival=2026-11-20', ['Accept-Language' => 'en'])->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0', ['id' => $this->champagne->id, 'name' => 'Champagne', 'description' => 'Served chilled on arrival.', 'price' => 4500, 'pricing' => 'per_unit', 'max_quantity' => 3])
        ->assertJsonPath('data.1.max_quantity', 1);

    // Arrivée le 20 à 16 h, il est le 18 à 20 h : moins de 48 h, plus de champagne.
    Date::setTestNow('2026-11-18 20:00:00');
    $this->getJson('/api/apartments/le-loft/extras?arrival=2026-11-20')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Petit-déjeuner');
    $this->getJson('/api/apartments/le-loft/extras')->assertJsonValidationErrors('arrival');
    $this->getJson('/api/apartments/inconnu/extras?arrival=2026-11-20')->assertNotFound();
});

it('chiffre les options dans le devis et les fige dans la réservation, au prix du serveur', function () {
    $stay = ['apartment' => 'le-loft', 'arrival' => '2026-11-20', 'departure' => '2026-11-22', 'adults' => 2];
    $extras = ['extras' => [['id' => $this->champagne->id, 'quantity' => 2], ['id' => $this->breakfast->id]]];

    // 2 nuits × 100 € + 2 × 45 € + 2 voyageurs × 2 nuits × 12 €.
    $this->postJson('/api/quotes', [...$stay, ...$extras])->assertOk()
        ->assertJsonPath('data.extras', 9000 + 4800)
        ->assertJsonPath('data.total', 20000 + 13800);

    $this->postJson('/api/bookings', [...$stay, ...$extras, 'name' => 'Camille Martin', 'email' => 'camille@example.com',
        'phone' => '+33 6 12 34 56 78', 'country' => 'FR', 'accept_terms' => true, 'total' => 1])->assertCreated();

    $booking = Booking::query()->sole();
    expect($booking->extras_amount)->toBe(13800)
        ->and($booking->total_amount)->toBe(33800)
        ->and($booking->extras()->orderBy('id')->pluck('quantity')->all())->toBe([2, 1]);
});

it('refuse une option commandée trop tard ou deux fois', function () {
    Date::setTestNow('2026-11-18 20:00:00');
    $this->postJson('/api/quotes', ['apartment' => 'le-loft', 'arrival' => '2026-11-20', 'departure' => '2026-11-22', 'adults' => 2, 'extras' => [['id' => $this->champagne->id]]])
        ->assertUnprocessable()->assertJsonPath('code', 'extra_not_available');
    $this->postJson('/api/quotes', ['apartment' => 'le-loft', 'arrival' => '2026-11-20', 'departure' => '2026-11-22', 'adults' => 2, 'extras' => [['id' => $this->breakfast->id], ['id' => $this->breakfast->id]]])
        ->assertJsonValidationErrors('extras.0.id');
});
