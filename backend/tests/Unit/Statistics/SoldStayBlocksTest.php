<?php

use App\Enums\BlockType;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

function soldStayBlocks(array $blocks): SoldStayBlocks
{
    $availability = Mockery::mock(AvailabilityRepositoryInterface::class);
    $availability->allows('planningBlocks')->andReturn(new Collection($blocks));

    return new SoldStayBlocks($availability, new NightCounter);
}

it('compte une date Booking.com comme un séjour, sauf si elle répète une fermeture du site', function () {
    $redundant = memoryIcalBlock(1, '2026-11-10', '2026-11-12', 'booking', 'CLOSED - Not available')
        ->forceFill(['has_conflict' => true, 'is_redundant_closure' => true]);
    $nights = new NightCounter;

    $blocks = soldStayBlocks([memoryIcalBlock(1, '2026-11-02', '2026-11-05', 'booking', 'CLOSED - Not available'), $redundant])
        ->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'));

    expect($blocks->map(fn ($block) => $nights->kind($block))->all())->toBe(['platform', 'closed'])
        ->and($nights->count($blocks, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01')))
        ->toBe(['direct' => 0, 'platform' => 3, 'closed' => 2])
        // Le bloc enregistré n'est pas modifié : seule sa copie compte comme fermée.
        ->and($redundant->type)->toBe(BlockType::Ical);
});

it('ne garde que les logements demandés', function () {
    $service = soldStayBlocks([memoryBlock(1, '2026-11-02', '2026-11-05'), memoryBlock(2, '2026-11-02', '2026-11-05')]);

    expect($service->between(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01'), [2])->pluck('apartment_id')->all())->toBe([2]);
});
