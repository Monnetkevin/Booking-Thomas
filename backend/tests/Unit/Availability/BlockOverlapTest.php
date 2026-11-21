<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Services\Availability\BlockOverlap;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

function overlapOf(array $blocks): BlockOverlap
{
    return new BlockOverlap(new Collection($blocks));
}

function nights(string $from, string $to): array
{
    return [CarbonImmutable::parse($from), CarbonImmutable::parse($to)];
}

it('reconnaît des nuits toutes tenues par des fermetures, bout à bout ou de plateformes', function () {
    $overlap = overlapOf([
        memoryBlock(1, '2026-11-20', '2026-11-22'),
        memoryIcalBlock(1, '2026-11-22', '2026-11-25', summary: 'Airbnb (Not available)'),
    ]);

    expect($overlap->onlyClosuresCovering(...nights('2026-11-20', '2026-11-25')))->toBeTrue()
        ->and($overlap->onlyClosuresCovering(...nights('2026-11-21', '2026-11-24')))->toBeTrue();
});

it('refuse une couverture partielle, un trou entre deux fermetures ou un séjour dessous', function () {
    expect(overlapOf([memoryBlock(1, '2026-11-20', '2026-11-22')])->onlyClosuresCovering(...nights('2026-11-20', '2026-11-23')))->toBeFalse()
        ->and(overlapOf([memoryBlock(1, '2026-11-20', '2026-11-21'), memoryBlock(1, '2026-11-22', '2026-11-25')])->onlyClosuresCovering(...nights('2026-11-20', '2026-11-25')))->toBeFalse()
        ->and(overlapOf([memoryBlock(1, '2026-11-20', '2026-11-25'), memoryIcalBlock(1, '2026-11-21', '2026-11-22')])->onlyClosuresCovering(...nights('2026-11-20', '2026-11-25')))->toBeFalse()
        ->and(overlapOf([memoryBlock(1, '2026-11-20', '2026-11-25', BlockType::Booking)->setRelation('booking', memoryBooking(1, '2026-11-20', '2026-11-25', BookingStatus::Confirmed))])->onlyClosuresCovering(...nights('2026-11-20', '2026-11-25')))->toBeFalse()
        ->and(overlapOf([])->onlyClosuresCovering(...nights('2026-11-20', '2026-11-25')))->toBeFalse();
});

it('tient un bloc Booking.com pour une fermeture possible, un séjour Airbnb jamais', function () {
    expect(memoryIcalBlock(1, '2026-11-20', '2026-11-22', 'booking', 'CLOSED - Not available')->mayBeClosure())->toBeTrue()
        ->and(memoryIcalBlock(1, '2026-11-20', '2026-11-22', summary: 'Airbnb (Not available)')->mayBeClosure())->toBeTrue()
        ->and(memoryIcalBlock(1, '2026-11-20', '2026-11-22')->mayBeClosure())->toBeFalse()
        ->and(memoryBlock(1, '2026-11-20', '2026-11-22')->mayBeClosure())->toBeTrue();
});
