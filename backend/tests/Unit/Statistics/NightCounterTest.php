<?php

use App\Enums\ApartmentStatus;
use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Services\Statistics\NightCounter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

beforeEach(function () {
    $this->counter = new NightCounter;
    $this->from = CarbonImmutable::parse('2026-11-01');
    $this->to = CarbonImmutable::parse('2026-12-01');
});

function bookingBlock(BookingStatus $status, string $from = '2026-11-02', string $to = '2026-11-05'): AvailabilityBlock
{
    return memoryBlock(1, $from, $to, BlockType::Booking)->setRelation('booking', memoryBooking(1, $from, $to, $status));
}

it('classe chaque bloc : vendu en direct, sur une plateforme, fermé, ou rien', function () {
    expect($this->counter->kind(bookingBlock(BookingStatus::Confirmed)))->toBe('direct')
        ->and($this->counter->kind(bookingBlock(BookingStatus::Completed)))->toBe('direct')
        ->and($this->counter->kind(bookingBlock(BookingStatus::Pending)))->toBeNull()
        ->and($this->counter->kind(bookingBlock(BookingStatus::Cancelled)))->toBeNull()
        ->and($this->counter->kind(memoryBlock(1, '2026-11-02', '2026-11-05')))->toBe('closed')
        ->and($this->counter->kind(memoryIcalBlock(1, '2026-11-02', '2026-11-05')))->toBe('platform')
        ->and($this->counter->kind(memoryIcalBlock(1, '2026-11-02', '2026-11-05', summary: 'Airbnb (Not available)')))->toBe('closed')
        ->and($this->counter->kind(memoryIcalBlock(1, '2026-11-02', '2026-11-05', 'booking', 'CLOSED - Not available')))->toBe('platform');
});

it('compte une seule fois une nuit couverte par plusieurs blocs : site, puis plateforme, puis fermeture', function () {
    $blocks = collect([
        memoryBlock(1, '2026-11-10', '2026-11-13'),                                         // fermée sur le site
        memoryIcalBlock(1, '2026-11-10', '2026-11-13', summary: 'Airbnb (Not available)'),  // et sur Airbnb
        memoryIcalBlock(1, '2026-11-12', '2026-11-15'),                                     // séjour Airbnb en conflit
        bookingBlock(BookingStatus::Confirmed, '2026-11-14', '2026-11-16'),                 // réservation du site
        memoryBlock(2, '2026-11-10', '2026-11-13'),                                         // autre logement
    ]);

    // Logement 1 : 10 et 11 fermées, 12 et 13 Airbnb, 14 et 15 site ; logement 2 : 3 fermées.
    expect($this->counter->count($blocks, $this->from, $this->to))->toBe(['direct' => 2, 'platform' => 2, 'closed' => 5]);
});

it('compte les nuits dans [début, fin[, à cheval sur la période comprise', function () {
    $blocks = collect([
        bookingBlock(BookingStatus::Confirmed, '2026-10-30', '2026-11-03'),   // 2 nuits en novembre
        memoryIcalBlock(1, '2026-11-28', '2026-12-04'),                       // 3 nuits en novembre
        memoryIcalBlock(1, '2026-11-10', '2026-11-20', summary: 'Airbnb (Not available)'),
        memoryBlock(2, '2026-11-01', '2026-11-02'),
        bookingBlock(BookingStatus::Pending, '2026-11-15', '2026-11-18'),
    ]);

    expect($this->counter->count($blocks, $this->from, $this->to))->toBe(['direct' => 2, 'platform' => 3, 'closed' => 11]);
});

it('ne compte aucune nuit hors de la période', function () {
    $block = memoryBlock(1, '2026-10-01', '2026-10-05');

    expect($this->counter->overlap($block, $this->from, $this->to))->toBe(0)
        ->and($this->counter->overlap(memoryBlock(1, '2026-11-30', '2026-12-02'), $this->from, $this->to))->toBe(1);
});

it('compte les nuits de plateforme du mois, par logement et par plateforme, sans les fermetures', function () {
    $blocks = collect([
        memoryIcalBlock(1, '2026-11-28', '2026-12-04'),                                     // 3 nuits en novembre
        memoryIcalBlock(1, '2026-10-29', '2026-11-03'),                                     // 1er novembre (le 2, réservation du site)
        memoryIcalBlock(1, '2026-11-10', '2026-11-20', summary: 'Airbnb (Not available)'), // fermeture
        memoryIcalBlock(2, '2026-11-05', '2026-11-07', 'booking', 'CLOSED - Not available'),
        bookingBlock(BookingStatus::Confirmed),
    ]);

    expect($this->counter->platformNightsByApartment($blocks, $this->from, $this->to))
        ->toBe([1 => ['airbnb' => 4], 2 => ['booking' => 2]]);
});

it('ne compte qu’une fois, par plateforme, les nuits de deux séjours en conflit', function () {
    $blocks = collect([
        memoryIcalBlock(1, '2026-11-05', '2026-11-08'),                                     // Airbnb, retenu le premier
        memoryIcalBlock(1, '2026-11-06', '2026-11-09', 'booking', 'CLOSED - Not available'), // seul le 8 lui revient
    ]);

    expect($this->counter->platformNightsByApartment($blocks, $this->from, $this->to))->toBe([1 => ['airbnb' => 3, 'booking' => 1]])
        ->and($this->counter->count($blocks, $this->from, $this->to)['platform'])->toBe(4);
});

it('calcule l’occupation sur les nuits ouvertes, nulle sans nuit ouverte', function () {
    $nights = ['direct' => 10, 'platform' => 5, 'closed' => 30];

    expect($this->counter->occupancy($nights, 60))->toBe(50.0)
        ->and($this->counter->openNights($nights, 60))->toBe(30)
        ->and($this->counter->occupancy(['direct' => 0, 'platform' => 0, 'closed' => 30], 30))->toBeNull();
});

it('ne compte que les logements publiés, chacun depuis sa création', function () {
    $old = (new Apartment)->forceFill(['id' => 1, 'status' => ApartmentStatus::Published, 'created_at' => '2025-01-01 09:00:00']);
    $new = (new Apartment)->forceFill(['id' => 2, 'status' => ApartmentStatus::Published, 'created_at' => '2026-11-21 15:00:00']);
    $draft = (new Apartment)->forceFill(['id' => 3, 'status' => ApartmentStatus::Draft, 'created_at' => '2025-01-01 09:00:00']);

    $market = $this->counter->onMarket(new EloquentCollection([$old, $new, $draft]));

    // Novembre : 30 nuits pour le premier, 10 (du 21 au 30) pour le second, rien pour le brouillon.
    expect($market->modelKeys())->toBe([1, 2])
        ->and($this->counter->marketNights($market, $this->from, $this->to))->toBe(40);
});

it('compte les arrivées de séjours vendus, et celles du site', function () {
    $blocks = collect([
        bookingBlock(BookingStatus::Confirmed),
        bookingBlock(BookingStatus::Pending),
        memoryIcalBlock(1, '2026-11-10', '2026-11-12'),
        memoryIcalBlock(1, '2026-11-20', '2026-11-22', summary: 'Airbnb (Not available)'),
        memoryBlock(1, '2026-11-25', '2026-11-26'),
        memoryIcalBlock(1, '2026-10-30', '2026-11-02'),
    ]);

    expect($this->counter->arrivals($blocks, $this->from, $this->to))->toBe(['all' => 2, 'direct' => 1]);
});

it('compte une arrivée par séjour qui tient sa première nuit : deux séjours en conflit font une arrivée', function () {
    $blocks = collect([
        bookingBlock(BookingStatus::Confirmed, '2026-11-10', '2026-11-12'),
        memoryIcalBlock(1, '2026-11-10', '2026-11-12'),                                     // séjour Airbnb en conflit
        memoryBlock(1, '2026-11-20', '2026-11-22'),                                         // fermeture du site
        memoryIcalBlock(1, '2026-11-20', '2026-11-22', summary: 'Airbnb (Not available)'),  // fermée aussi sur Airbnb
        memoryIcalBlock(1, '2026-11-25', '2026-11-27'),
        memoryIcalBlock(2, '2026-11-10', '2026-11-12'),                                     // autre logement
    ]);

    expect($this->counter->arrivals($blocks, $this->from, $this->to))->toBe(['all' => 3, 'direct' => 1])
        ->and($this->counter->count($blocks, $this->from, $this->to))->toBe(['direct' => 2, 'platform' => 4, 'closed' => 2]);
});
