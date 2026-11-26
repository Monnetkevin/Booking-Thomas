<?php

use App\Enums\BookingStatus;
use App\Enums\ExtraPricing;
use App\Exceptions\Extra\ExtraNotAvailableException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Extra;
use App\Models\RatePeriod;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\Stay;
use App\Services\Extra\ExtraChoice;
use App\Services\Extra\ExtraSelector;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\Quote;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    Date::setTestNow('2026-11-20 10:00:00');
    $this->apartment = Apartment::factory()->create(['cleaning_fee' => 4000, 'check_in_time' => '16:00:00']);
    RatePeriod::factory()->for($this->apartment)->create(['starts_on' => '2026-11-01', 'ends_on' => '2027-01-31', 'nightly_price' => 8000, 'weekend_supplement' => null]);
    app(SettingRepositoryInterface::class)->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 0]);
    $this->stay = Stay::fromStrings('2026-12-01', '2026-12-04', 2, 1);
});

/** Option proposée dans le logement du test. */
function extraFor(Apartment $apartment, array $attributes = []): Extra
{
    $extra = Extra::factory()->create($attributes);
    $extra->apartments()->attach($apartment);

    return $extra;
}

/** @param list<ExtraChoice> $choices */
function priceWith(array $choices, bool $enforce = true): Quote
{
    $selected = app(ExtraSelector::class)->select(test()->apartment, test()->stay, $choices, $enforce);

    return app(PriceCalculator::class)->calculate(test()->apartment, test()->stay, $selected);
}

it('chiffre chaque mode : séjour, nuit, personne, personne et nuit, unité ; une option gratuite à 0 €', function () {
    $champagne = extraFor($this->apartment, ['name' => ['fr' => 'Champagne', 'en' => 'Champagne'], 'price' => 4500, 'pricing' => ExtraPricing::PerUnit, 'max_quantity' => 3]);
    $parking = extraFor($this->apartment, ['name' => ['fr' => 'Parking', 'en' => 'Parking'], 'price' => 800, 'pricing' => ExtraPricing::PerNight]);
    $sheets = extraFor($this->apartment, ['name' => ['fr' => 'Draps', 'en' => 'Sheets'], 'price' => 1000, 'pricing' => ExtraPricing::PerGuest]);
    $breakfast = extraFor($this->apartment, ['name' => ['fr' => 'Petit-déjeuner', 'en' => 'Breakfast'], 'price' => 1200, 'pricing' => ExtraPricing::PerGuestNight]);
    $cot = extraFor($this->apartment, ['name' => ['fr' => 'Lit bébé', 'en' => 'Cot'], 'price' => 0, 'pricing' => ExtraPricing::PerStay]);

    $quote = priceWith([
        new ExtraChoice($champagne->id, 2), new ExtraChoice($parking->id, 5), new ExtraChoice($sheets->id),
        new ExtraChoice($breakfast->id), new ExtraChoice($cot->id),
    ]);

    // 3 nuits, 3 voyageurs : 2 × 45 + 3 × 8 + 3 × 10 + 9 × 12 + 0.
    expect(array_column($quote->extraItems, 'amount'))->toBe([9000, 2400, 3000, 10800, 0])
        ->and(array_column($quote->extraItems, 'quantity'))->toBe([2, 1, 1, 1, 1])
        ->and($quote->extras)->toBe(25200)
        ->and($quote->total)->toBe(24000 + 4000 + 25200)
        ->and(collect($quote->lines)->where('type', 'extra')->pluck('label')->all())->toBe(['2 × Champagne', 'Parking', 'Draps', 'Petit-déjeuner', 'Lit bébé']);
});

it('refuse une option d’un autre logement, désactivée, en trop grand nombre ou commandée trop tard', function () {
    $other = Extra::factory()->create();
    $inactive = extraFor($this->apartment, ['is_active' => false]);
    $champagne = extraFor($this->apartment, ['max_quantity' => 2]);

    foreach ([new ExtraChoice($other->id), new ExtraChoice($inactive->id), new ExtraChoice($champagne->id, 3), new ExtraChoice(999)] as $choice) {
        expect(fn () => priceWith([$choice]))->toThrow(ExtraNotAvailableException::class);
    }

    // 48 h avant l'arrivée du 1er décembre à 16 h : jusqu'au 29 novembre à 16 h.
    Date::setTestNow('2026-11-29 16:00:00');
    expect(priceWith([new ExtraChoice($champagne->id)])->extras)->toBe(4500);
    Date::setTestNow('2026-11-29 16:01:00');
    expect(fn () => priceWith([new ExtraChoice($champagne->id)]))->toThrow(ExtraNotAvailableException::class);
    // L'hôte n'est pas tenu par le délai.
    expect(priceWith([new ExtraChoice($champagne->id)], enforce: false)->extras)->toBe(4500);
});

it('fige les options dans la réservation saisie à la main, même avec un prix convenu', function () {
    $this->actingAs(User::factory()->create());
    $champagne = extraFor($this->apartment, ['name' => ['fr' => 'Champagne', 'en' => 'Champagne'], 'price' => 4500]);
    $payload = ['apartment_id' => $this->apartment->id, 'arrival' => '2026-12-01', 'departure' => '2026-12-04', 'adults' => 2,
        'guest_name' => 'Camille Martin', 'paid' => false, 'send_confirmation' => false, 'extras' => [['id' => $champagne->id, 'quantity' => 2]]];

    $reference = $this->postJson('/api/admin/bookings', $payload)->assertCreated()->json('data.reference');
    $booking = Booking::query()->where('reference', $reference)->sole();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->extras_amount)->toBe(9000)
        ->and($booking->total_amount)->toBe(24000 + 4000 + 9000)
        ->and($booking->extras()->sole()->only(['name', 'quantity', 'unit_amount', 'amount']))->toBe(['name' => 'Champagne', 'quantity' => 2, 'unit_amount' => 4500, 'amount' => 9000]);

    // Sans tarif pour ces nuits, le prix convenu comprend les options, notées pour être préparées.
    $agreed = $this->postJson('/api/admin/bookings', [...$payload, 'arrival' => '2027-03-01', 'departure' => '2027-03-03', 'total_amount' => 30000])->assertCreated()->json('data.reference');
    $booking = Booking::query()->where('reference', $agreed)->sole();
    expect($booking->total_amount)->toBe(30000)->and($booking->extras()->sole()->quantity)->toBe(2);
});
