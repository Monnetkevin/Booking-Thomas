<?php

use App\Enums\ApartmentStatus;
use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Enums\ExtraPricing;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Extra;
use App\Models\RatePeriod;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    RateLimiter::clear('bookings');
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 0]);
    $this->loft = Apartment::factory()->create([
        'slug' => 'le-loft', 'status' => ApartmentStatus::Published, 'capacity' => 4, 'min_stay' => 1, 'max_stay' => 14,
        'cleaning_fee' => 0, 'turnover_days' => 0, 'check_in_time' => '16:00:00',
    ]);
    RatePeriod::factory()->for($this->loft)->create(['starts_on' => '2026-11-01', 'ends_on' => '2027-03-31', 'nightly_price' => 10000, 'weekend_supplement' => null]);
    $this->champagne = Extra::factory()->create(['name' => ['fr' => 'Champagne', 'en' => 'Champagne'], 'price' => 4500, 'pricing' => ExtraPricing::PerUnit, 'lead_time_hours' => 48]);
    $this->champagne->apartments()->attach($this->loft);
    // « 4 nuits ou plus au Loft, le champagne est offert » (exemple de la spec).
    Coupon::factory()->create(['code' => 'LOFT4', 'automatic' => true, 'target' => CouponTarget::Extra, 'type' => CouponType::FreeExtra, 'extra_id' => $this->champagne->id, 'min_nights' => 4])
        ->apartments()->attach($this->loft);
});

function offerQuote(string $departure, array $extra = []): TestResponse
{
    return test()->postJson('/api/quotes', [...['apartment' => 'le-loft', 'arrival' => '2026-11-17', 'departure' => $departure, 'adults' => 2], ...$extra]);
}

it('applique l’offre dès que ses conditions sont remplies, sans code', function () {
    offerQuote('2026-11-20')->assertJsonPath('data.discount', 0)->assertJsonPath('data.coupon', null)->assertJsonPath('data.extras', 0);

    offerQuote('2026-11-21')->assertOk()
        ->assertJsonPath('data.coupon', 'LOFT4')
        ->assertJsonPath('data.extras', 4500)
        ->assertJsonPath('data.discount', 4500)
        ->assertJsonPath('data.total', 40000)
        ->assertJsonPath('data.lines.1', ['type' => 'extra', 'label' => '1 × Champagne', 'quantity' => 1, 'unit_amount' => 4500, 'amount' => 4500])
        ->assertJsonPath('data.lines.2', ['type' => 'discount', 'label' => 'Offre LOFT4', 'amount' => -4500]);
});

it('garde la plus avantageuse des offres, et un code saisi la remplace', function () {
    Coupon::factory()->create(['code' => 'HIVER', 'automatic' => true, 'type' => CouponType::Percent, 'value' => 5]);
    Coupon::factory()->create(['code' => 'DIX', 'type' => CouponType::Fixed, 'value' => 1000]);

    // 5 % de 400 € = 20 € < champagne offert (45 €).
    offerQuote('2026-11-21')->assertJsonPath('data.coupon', 'LOFT4');
    // 3 nuits : le champagne n'est pas offert, 5 % de 300 € s'appliquent.
    offerQuote('2026-11-20')->assertJsonPath('data.coupon', 'HIVER')->assertJsonPath('data.discount', 1500);

    $this->postJson('/api/coupons/validate', ['apartment' => 'le-loft', 'arrival' => '2026-11-17', 'departure' => '2026-11-21', 'adults' => 2, 'code' => 'dix'])
        ->assertJsonPath('data.coupon', 'DIX')->assertJsonPath('data.discount', 1000)->assertJsonPath('data.extras', 0);
});

it('ne propose pas une option offerte qui n’est plus commandable, et compte l’offre à la réservation', function () {
    // Arrivée dans moins de 48 h : le champagne ne peut plus être préparé.
    Date::setTestNow('2026-11-16 10:00:00');
    offerQuote('2026-11-21')->assertJsonPath('data.coupon', null)->assertJsonPath('data.extras', 0);

    Date::setTestNow('2026-11-10 14:00:00');
    $this->postJson('/api/bookings', ['apartment' => 'le-loft', 'arrival' => '2026-11-17', 'departure' => '2026-11-21', 'adults' => 2,
        'name' => 'Camille Martin', 'email' => 'camille@example.com', 'phone' => '+33 6 12 34 56 78', 'country' => 'FR', 'accept_terms' => true])->assertCreated();

    $booking = Booking::query()->sole();
    expect($booking->discount_amount)->toBe(4500)
        ->and($booking->extras()->sole()->name)->toBe('Champagne')
        ->and(CouponRedemption::query()->sole()->code)->toBe('LOFT4');
});
