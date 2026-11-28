<?php

use App\Enums\IcalProvider;
use App\Exceptions\Ical\IcalSyncException;
use App\Services\Ical\IcalFetcher;
use GuzzleHttp\Psr7\Uri;

it('ne suit une redirection qu’en https et vers un domaine de la plateforme', function () {
    $policy = IcalFetcher::redirectPolicy(IcalProvider::Airbnb);

    expect($policy['max'])->toBe(3)->and($policy['protocols'])->toBe(['https']);

    // Domaine Airbnb : accepté.
    ($policy['on_redirect'])(null, null, new Uri('https://www.airbnb.com/calendar/ical/1.ics'));

    // Réseau interne ou autre site : refusé.
    expect(fn () => ($policy['on_redirect'])(null, null, new Uri('https://169.254.169.254/latest/meta-data')))
        ->toThrow(IcalSyncException::class, '169.254.169.254');
});
