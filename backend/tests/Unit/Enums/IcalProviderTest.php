<?php

use App\Enums\IcalProvider;

it('reconnaît une date fermée sur Airbnb, pas une réservation', function (?string $summary, bool $closure) {
    expect(IcalProvider::Airbnb->isClosure($summary))->toBe($closure);
})->with([
    'réservation' => ['Reserved', false],
    'date fermée' => ['Airbnb (Not available)', true],
    'casse différente' => ['AIRBNB (NOT AVAILABLE)', true],
    'sans résumé' => [null, false],
    'résumé vide' => ['', false],
]);

it('ne tranche pas sur Booking.com : le même résumé pour une réservation et une fermeture', function () {
    expect(IcalProvider::Booking->isClosure('CLOSED - Not available'))->toBeFalse()
        ->and(IcalProvider::Booking->mixesStaysAndClosures())->toBeTrue()
        ->and(IcalProvider::Airbnb->mixesStaysAndClosures())->toBeFalse();
});

it('n’accepte que les adresses de la plateforme', function (IcalProvider $provider, string $host, bool $allowed) {
    expect($provider->allowsHost($host))->toBe($allowed);
})->with([
    [IcalProvider::Airbnb, 'www.airbnb.fr', true],
    [IcalProvider::Airbnb, 'www.airbnb.co.uk', true],
    [IcalProvider::Airbnb, 'airbnb.fr.evil.com', false],
    [IcalProvider::Booking, 'admin.booking.com', true],
    [IcalProvider::Booking, 'ical.booking.com', true],
    [IcalProvider::Booking, 'booking.com.evil.net', false],
    [IcalProvider::Booking, 'notbooking.com', false],
    [IcalProvider::Airbnb, 'admin.booking.com', false],
]);
