<?php

use App\Enums\BlockType;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/** @return array{0: SoldStayBlocks, 1: list<string>} */
function soldStayBlocks(array $blocks, array $payouts = []): array
{
    $availability = Mockery::mock(AvailabilityRepositoryInterface::class);
    $availability->allows('planningBlocks')->andReturn(new Collection($blocks));
    $repository = Mockery::mock(PlatformPayoutRepositoryInterface::class);
    $asked = new ArrayObject;
    $repository->allows('arrivingBetween')->andReturnUsing(function ($from) use ($payouts, $asked) {
        $asked[] = $from->toDateString();

        return new Collection($payouts);
    });

    return [new SoldStayBlocks($availability, $repository, new NightCounter), $asked];
}

it('compte une date Booking.com sans versement comme une fermeture, avec versement comme un séjour', function () {
    [$service] = soldStayBlocks(
        [
            memoryIcalBlock(1, '2026-11-02', '2026-11-05', 'booking', 'CLOSED - Not available'),
            memoryIcalBlock(1, '2026-11-10', '2026-11-12', 'booking', 'CLOSED - Not available'),
        ],
        [memoryPayout('booking', 1, '2026-11-10', 2, 20000)],
    );
    $nights = new NightCounter;

    $blocks = $service->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'));

    expect($blocks->map(fn ($block) => $nights->kind($block))->all())->toBe(['closed', 'platform'])
        ->and($nights->count($blocks, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01')))
        ->toBe(['direct' => 0, 'platform' => 2, 'closed' => 3]);
});

it('cherche les versements depuis le premier bloc, et seulement s’il y a un calendrier Booking.com', function () {
    [$service, $asked] = soldStayBlocks([memoryIcalBlock(1, '2026-10-28', '2026-11-03', 'booking', 'CLOSED - Not available')]);
    $service->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'));
    expect($asked->getArrayCopy())->toBe(['2026-10-28']);

    [$service, $asked] = soldStayBlocks([memoryIcalBlock(1, '2026-11-02', '2026-11-05'), memoryBlock(1, '2026-11-10', '2026-11-12', BlockType::Manual)]);
    $blocks = $service->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'));
    expect($asked->getArrayCopy())->toBe([])
        ->and($blocks)->toHaveCount(2);
});

it('ne garde que les logements demandés', function () {
    [$service] = soldStayBlocks([memoryBlock(1, '2026-11-02', '2026-11-05'), memoryBlock(2, '2026-11-02', '2026-11-05')]);

    expect($service->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'), [2])->pluck('apartment_id')->all())->toBe([2]);
});

it('import des revenus désactivé : compte une date Booking.com comme un séjour, sauf si elle répète une fermeture du site', function () {
    $availability = Mockery::mock(AvailabilityRepositoryInterface::class);
    $redundant = memoryIcalBlock(1, '2026-11-10', '2026-11-12', 'booking', 'CLOSED - Not available')
        ->forceFill(['has_conflict' => true, 'is_redundant_closure' => true]);
    $availability->allows('planningBlocks')->andReturn(new Collection([
        memoryIcalBlock(1, '2026-11-02', '2026-11-05', 'booking', 'CLOSED - Not available'),
        $redundant,
    ]));
    $repository = Mockery::mock(PlatformPayoutRepositoryInterface::class);
    $repository->shouldNotReceive('arrivingBetween');
    $nights = new NightCounter;

    $blocks = (new SoldStayBlocks($availability, $repository, $nights, payoutsEnabled: false))
        ->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'));

    expect($blocks->map(fn ($block) => $nights->kind($block))->all())->toBe(['platform', 'closed'])
        ->and($redundant->type)->toBe(BlockType::Ical);
});
