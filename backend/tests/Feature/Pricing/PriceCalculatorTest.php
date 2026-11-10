<?php

use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\Stay;
use App\Services\Pricing\MissingRateException;
use App\Services\Pricing\PriceCalculator;

beforeEach(function () {
    $this->apartment = Apartment::factory()->create(['cleaning_fee' => 4000]);
    RatePeriod::factory()->for($this->apartment)->create([
        'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'nightly_price' => 8000, 'weekend_supplement' => null,
    ]);
    RatePeriod::factory()->for($this->apartment)->create([
        'starts_on' => '2026-12-01', 'ends_on' => '2027-01-04', 'nightly_price' => 9500, 'weekend_supplement' => 1500,
    ]);
    $this->settings = app(SettingRepositoryInterface::class);
    $this->settings->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 150]);
    $this->calculator = app(PriceCalculator::class);
});

it('calcule nuit par nuit avec le supplément des nuits du vendredi et du samedi', function () {
    // Arrivée vendredi 4 décembre, départ lundi 7 : nuits ven., sam., dim.
    $quote = $this->calculator->calculate($this->apartment, Stay::fromStrings('2026-12-04', '2026-12-07', 2));

    expect(array_column($quote->nights, 'price'))->toBe([11000, 11000, 9500])
        ->and($quote->accommodation)->toBe(31500)
        ->and($quote->cleaningFee)->toBe(4000)
        ->and($quote->touristTax)->toBe(900)
        ->and($quote->total)->toBe(36400);
});

it('détaille les lignes par prix de nuit', function () {
    $quote = $this->calculator->calculate($this->apartment, Stay::fromStrings('2026-12-04', '2026-12-07', 2));

    expect(array_column($quote->lines, 'label'))->toBe([
        "2 nuits × 110\u{00A0}€",
        "1 nuit × 95\u{00A0}€",
        'Frais de ménage',
        'Taxe de séjour (2 adulte(s), 3 nuit(s))',
    ])->and(array_sum(array_column($quote->lines, 'amount')))->toBe($quote->total);
});

it('applique à chaque nuit le tarif de sa période', function () {
    $quote = $this->calculator->calculate($this->apartment, Stay::fromStrings('2026-11-29', '2026-12-02', 1));

    expect(array_column($quote->nights, 'price'))->toBe([8000, 8000, 9500]);
});

it('exonère les mineurs de taxe de séjour', function () {
    $quote = $this->calculator->calculate($this->apartment, Stay::fromStrings('2026-11-23', '2026-11-25', 2, 2));

    expect($quote->touristTax)->toBe(150 * 2 * 2);
});

it('calcule la taxe en pourcentage plafonné par adulte et par nuit', function () {
    // 5 % de (80 € / 2 voyageurs) = 2 €, plafonné à 1,50 € ; 1 adulte payant, 2 nuits.
    $this->settings->put('tourist_tax', ['mode' => 'percentage', 'percentage' => 5, 'cap_per_adult_night' => 150]);

    expect($this->calculator->calculate($this->apartment, Stay::fromStrings('2026-11-23', '2026-11-25', 1, 1))->touristTax)->toBe(300);

    $this->settings->put('tourist_tax', ['mode' => 'percentage', 'percentage' => 5, 'cap_per_adult_night' => null]);
    expect($this->calculator->calculate($this->apartment, Stay::fromStrings('2026-11-23', '2026-11-25', 1, 1))->touristTax)->toBe(400);
});

it('omet les lignes à zéro', function () {
    $this->settings->put('tourist_tax', ['mode' => 'per_adult_night', 'amount' => 0]);
    $this->apartment->update(['cleaning_fee' => 0]);

    $quote = $this->calculator->calculate($this->apartment, Stay::fromStrings('2026-11-23', '2026-11-25', 2));

    expect(array_column($quote->lines, 'type'))->toBe(['accommodation'])->and($quote->total)->toBe(16000);
});

it('refuse une nuit sans tarif', function () {
    $this->calculator->calculate($this->apartment, Stay::fromStrings('2027-01-03', '2027-01-06', 2));
})->throws(MissingRateException::class);
