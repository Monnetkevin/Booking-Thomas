<?php

use App\Enums\ApartmentStatus;
use App\Enums\BookingStatus;
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
use App\Services\Booking\BookingService;
use App\Services\Booking\RefundCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Date::setTestNow('2026-11-10 14:00:00');
    RateLimiter::clear('bookings');
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 100]);
    $this->apartment = Apartment::factory()->create([
        'slug' => 'le-loft', 'status' => ApartmentStatus::Published, 'capacity' => 4, 'min_stay' => 1, 'max_stay' => 14,
        'cleaning_fee' => 4000, 'turnover_days' => 0, 'check_in_time' => '16:00:00',
    ]);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2027-03-31', 'nightly_price' => 10000, 'weekend_supplement' => null]);
    $this->champagne = Extra::factory()->create(['name' => ['fr' => 'Champagne', 'en' => 'Champagne'], 'price' => 4500, 'pricing' => ExtraPricing::PerUnit, 'max_quantity' => 3, 'lead_time_hours' => 48]);
    $this->champagne->apartments()->attach($this->apartment);
});

/** Séjour du 17 au 21 novembre (4 nuits, 2 adultes) : 400 € + 40 € de ménage + 8 € de taxe. */
function couponStay(array $overrides = []): array
{
    return [...['apartment' => 'le-loft', 'arrival' => '2026-11-17', 'departure' => '2026-11-21', 'adults' => 2], ...$overrides];
}

it('applique chaque type de réduction, sans jamais toucher à la taxe de séjour', function (array $coupon, array $extras, int $discount) {
    Coupon::factory()->create(['code' => 'TEST', 'extra_id' => $this->champagne->id, ...$coupon]);

    $extras = array_map(fn (array $item) => [...$item, 'id' => $this->champagne->id], $extras);
    $response = $this->postJson('/api/coupons/validate', couponStay(['code' => 'test', 'extras' => $extras]))->assertOk()
        ->assertJsonPath('data.discount', $discount)
        ->assertJsonPath('data.coupon', 'TEST')
        ->assertJsonPath('data.tourist_tax', 800);

    $extrasAmount = $response->json('data.extras');
    expect($response->json('data.total'))->toBe(40000 + 4000 + $extrasAmount + 800 - $discount)
        ->and(collect($response->json('data.lines'))->firstWhere('type', 'discount'))->toMatchArray(['label' => 'Code TEST', 'amount' => -$discount]);
})->with([
    '10 % du séjour (hébergement et ménage)' => [['target' => CouponTarget::Stay, 'type' => CouponType::Percent, 'value' => 10], [], 4400],
    '20 € sur le séjour' => [['target' => CouponTarget::Stay, 'type' => CouponType::Fixed, 'value' => 2000], [], 2000],
    'montant plafonné à l’option visée' => [['target' => CouponTarget::Extra, 'type' => CouponType::Fixed, 'value' => 99900], [['id' => 0]], 4500],
    '50 % sur l’option et le séjour' => [['target' => CouponTarget::Both, 'type' => CouponType::Percent, 'value' => 50], [['id' => 0]], 24250],
    'option offerte, ajoutée si absente' => [['target' => CouponTarget::Extra, 'type' => CouponType::FreeExtra, 'free_quantity' => 1], [], 4500],
    'une seule bouteille offerte sur deux' => [['target' => CouponTarget::Extra, 'type' => CouponType::FreeExtra, 'free_quantity' => 1], [['id' => 0, 'quantity' => 2]], 4500],
]);

it('refuse avec la même erreur un code inconnu, inactif, expiré, d’un autre logement, trop court ou épuisé', function (array $coupon) {
    Coupon::factory()->create(['code' => 'TEST', 'value' => 10, ...$coupon]);

    $this->postJson('/api/coupons/validate', couponStay(['code' => 'TEST']))->assertUnprocessable()
        ->assertJsonPath('code', 'coupon_invalid')
        ->assertJsonPath('message', 'Ce code promo ne peut pas être utilisé pour ce séjour.');
})->with([
    'inactif' => [['is_active' => false]],
    'expiré' => [['ends_on' => '2026-11-09']],
    'pas encore valable' => [['starts_on' => '2026-11-11']],
    'trop peu de nuits' => [['min_nights' => 5]],
    'montant minimum' => [['min_amount' => 50000]],
    'offre automatique' => [['automatic' => true]],
    'option visée absente' => [['target' => CouponTarget::Extra, 'type' => CouponType::Percent]],
]);

it('refuse un code inconnu et un code réservé à un autre logement', function () {
    $this->postJson('/api/coupons/validate', couponStay(['code' => 'NOPE']))->assertJsonPath('code', 'coupon_invalid');
    Coupon::factory()->create(['code' => 'AILLEURS'])->apartments()->attach(Apartment::factory()->create());
    $this->postJson('/api/coupons/validate', couponStay(['code' => 'AILLEURS']))->assertJsonPath('code', 'coupon_invalid');
});

it('compte l’utilisation à la réservation, vérifie la limite par e-mail, et la libère si la réservation expire', function () {
    Coupon::factory()->create(['code' => 'BIENVENUE', 'type' => CouponType::Fixed, 'value' => 2000, 'max_uses' => 2, 'max_uses_per_email' => 1]);
    $book = fn (string $email, string $arrival) => $this->postJson('/api/bookings', [
        ...couponStay(['arrival' => $arrival, 'departure' => Date::parse($arrival)->addDays(2)->toDateString()]), 'coupon' => 'bienvenue',
        'name' => 'Camille Martin', 'email' => $email, 'phone' => '+33 6 12 34 56 78', 'country' => 'FR', 'accept_terms' => true,
    ]);

    $book('camille@example.com', '2026-11-17')->assertCreated();
    $first = Booking::query()->sole();
    expect($first->discount_amount)->toBe(2000)
        ->and($first->total_amount)->toBe(20000 + 4000 - 2000 + 400)
        ->and(CouponRedemption::query()->sole()->only(['email', 'code', 'discount_amount']))->toBe(['email' => 'camille@example.com', 'code' => 'BIENVENUE', 'discount_amount' => 2000]);

    // Même e-mail (casse différente) : limite par voyageur atteinte.
    $book('CAMILLE@example.com', '2026-11-24')->assertUnprocessable()->assertJsonPath('code', 'coupon_invalid');
    $book('paul@example.com', '2026-11-24')->assertCreated();
    // Deux utilisations sur deux.
    $book('julie@example.com', '2026-12-01')->assertJsonPath('code', 'coupon_invalid');

    app(BookingService::class)->expire($first);
    expect($first->refresh()->status)->toBe(BookingStatus::Expired);
    $book('julie@example.com', '2026-12-01')->assertCreated();
});

it('ne rend pas à l’annulation la part du ménage couverte par la réduction', function () {
    $booking = Booking::factory()->for($this->apartment)->create([
        'arrival' => '2026-12-10', 'departure' => '2026-12-11', 'accommodation_amount' => 10000, 'cleaning_fee' => 10000, 'extras_amount' => 0,
        'discount_amount' => 12000, 'tourist_tax' => 100, 'total_amount' => 8100,
        'cancellation_policy' => ['policy' => 'strict', 'tiers' => [['days_before' => 0, 'refund_percent' => 0]]],
    ]);

    // Barème à 0 % : l'hébergement n'est pas rendu, le ménage seulement pour sa part payée (80 €), la taxe en entier.
    expect(app(RefundCalculator::class)->propose($booking, CarbonImmutable::parse('2026-11-20'))->byPolicy)->toBe(8000 + 100);
});

it('rend au remboursement la part du ménage et des options vraiment payée, selon la cible du code', function () {
    $booking = fn (int $discount, int $extrasDiscount) => Booking::factory()->for($this->apartment)->create([
        'arrival' => '2026-12-10', 'departure' => '2026-12-11', 'accommodation_amount' => 10000, 'cleaning_fee' => 5000, 'extras_amount' => 3000,
        'discount_amount' => $discount, 'extras_discount_amount' => $extrasDiscount, 'tourist_tax' => 100, 'total_amount' => 18100 - $discount,
        'cancellation_policy' => ['policy' => 'strict', 'tiers' => [['days_before' => 0, 'refund_percent' => 0]]],
    ]);
    $refund = fn (Booking $booking) => app(RefundCalculator::class)->propose($booking, CarbonImmutable::parse('2026-11-20'))->byPolicy;

    // Code de 120 € sur le séjour : 100 € sur l'hébergement, 20 € sur le ménage ; seuls 30 € de ménage ont été payés.
    expect($refund($booking(12000, 0)))->toBe(3000 + 100)
        // Option offerte (30 €) : le ménage, payé en entier, est rendu en entier.
        ->and($refund($booking(3000, 3000)))->toBe(5000 + 100);
});
