<?php

use App\Services\PlatformImport\PlatformRow;
use Carbon\CarbonImmutable;

function platformRow(int $nights, int $net, ?int $fees = null): PlatformRow
{
    $arrival = CarbonImmutable::parse('2026-11-04');

    return new PlatformRow(2, 'HM1', 'Studio', null, $arrival, $arrival->addDays($nights), null, $fees, $net, 'k');
}

it('n’accepte qu’un code et une annonce présents et assez courts', function () {
    expect(PlatformRow::fits('HM1', 'Studio'))->toBeTrue()
        ->and(PlatformRow::fits('', 'Studio'))->toBeFalse()
        ->and(PlatformRow::fits('HM1', ''))->toBeFalse()
        ->and(PlatformRow::fits(str_repeat('a', PlatformRow::MAX_CODE + 1), 'Studio'))->toBeFalse()
        ->and(PlatformRow::fits('HM1', str_repeat('é', PlatformRow::MAX_LISTING)))->toBeTrue()
        ->and(PlatformRow::fits('HM1', str_repeat('é', PlatformRow::MAX_LISTING + 1)))->toBeFalse();
});

it('garde les nuits et montants dans les bornes de la base, frais négatifs compris', function () {
    expect(platformRow(3, 80000)->inRange())->toBeTrue()
        ->and(platformRow(3, -80000, -2400)->inRange())->toBeTrue()
        ->and(platformRow(PlatformRow::MAX_NIGHTS + 1, 100)->inRange())->toBeFalse()
        ->and(platformRow(3, PlatformRow::MAX_AMOUNT + 1)->inRange())->toBeFalse()
        ->and(platformRow(3, 100, -PlatformRow::MAX_AMOUNT - 1)->inRange())->toBeFalse()
        ->and(platformRow(3, 100)->nights())->toBe(3);
});

it('donne une clé stable, différente si une partie change', function () {
    expect(PlatformRow::key('HM1', '2026-11-04'))->toBe(PlatformRow::key('HM1', '2026-11-04'))
        ->and(PlatformRow::key('HM1', '2026-11-04'))->not->toBe(PlatformRow::key('HM1', '2026-11-05'));
});
