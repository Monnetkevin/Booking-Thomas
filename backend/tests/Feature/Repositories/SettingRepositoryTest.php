<?php

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;

beforeEach(fn () => $this->settings = app(SettingRepositoryInterface::class));

it('lit un réglage ou la valeur par défaut', function () {
    $this->settings->put('booking.min_notice_days', 1);

    expect($this->settings->get('booking.min_notice_days'))->toBe(1)
        ->and($this->settings->get('inconnu', 'défaut'))->toBe('défaut');
});

it('vide le cache à chaque écriture', function () {
    $this->settings->put('booking.horizon_months', 12);
    expect($this->settings->get('booking.horizon_months'))->toBe(12);

    $this->settings->put('booking.horizon_months', 18);

    expect($this->settings->get('booking.horizon_months'))->toBe(18);
});

it('n’écrase pas une valeur existante avec putIfMissing', function () {
    $this->settings->put('tourist_tax', ['amount' => 150]);

    $this->settings->putIfMissing('tourist_tax', ['amount' => 0]);
    $this->settings->putIfMissing('booking.min_notice_days', 2);

    expect($this->settings->get('tourist_tax'))->toBe(['amount' => 150])
        ->and($this->settings->get('booking.min_notice_days'))->toBe(2)
        ->and(Setting::count())->toBe(2);
});
