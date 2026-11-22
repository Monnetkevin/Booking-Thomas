<?php

use App\Enums\BookingStatus;
use App\Enums\StatisticsPeriod;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PlatformRevenueRepositoryInterface;
use App\Services\Export\CsvWriter;
use App\Services\PlatformRevenue\PlatformRevenueLedger;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\RevenueCalculator;
use App\Services\Statistics\SoldStayBlocks;
use App\Services\Statistics\StatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Rapport de novembre 2026 (période de 6 mois, de juin à novembre) sur un logement, à partir
 * de blocs, réservations et montants saisis en mémoire.
 *
 * @return array<string, mixed>
 */
function statisticsReport(array $blocks, array $earning = [], array $revenues = []): array
{
    $apartments = Mockery::mock(ApartmentRepositoryInterface::class);
    $apartments->allows('allOrdered')->andReturn(new Collection([(new Apartment)->forceFill(['id' => 1])->setTranslation('name', 'fr', 'Le Cocon')]));
    $availability = Mockery::mock(AvailabilityRepositoryInterface::class);
    $availability->allows('planningBlocks')->andReturn(new Collection($blocks));
    $bookings = Mockery::mock(BookingRepositoryInterface::class);
    $bookings->allows('earningArrivingBetween')->andReturn(new Collection($earning));
    $bookings->allows('soldArrivingBetween')->andReturn(new Collection(array_filter($earning, fn ($booking) => $booking->status !== BookingStatus::Cancelled)));
    $repository = Mockery::mock(PlatformRevenueRepositoryInterface::class);
    $repository->allows('between')->andReturnUsing(fn ($from, $to) => new Collection(array_values(
        array_filter($revenues, fn ($revenue) => $revenue->month->gte($from) && $revenue->month->lt($to)),
    )));

    $nights = new NightCounter;
    $service = new StatisticsService($apartments, $availability, new SoldStayBlocks($availability, $nights), $bookings, $repository, new PlatformRevenueLedger($nights), $nights, new RevenueCalculator, new CsvWriter);

    return $service->report(CarbonImmutable::parse('2026-11-20'), StatisticsPeriod::SixMonths);
}

function soldStay(string $arrival, string $departure, int $paid, BookingStatus $status = BookingStatus::Confirmed, int $refunded = 0): Booking
{
    return memoryBooking(1, $arrival, $departure, $status, ['paid_amount' => $paid, 'refunded_amount' => $refunded, 'accommodation_amount' => $paid]);
}

it('calcule le revenu par nuit sur les séjours vendus et payés, sans annulation ni saisie non payée', function () {
    $confirmed = soldStay('2026-11-02', '2026-11-04', 20000);
    $cancelled = soldStay('2026-11-10', '2026-11-14', 30000, BookingStatus::Cancelled);
    $unpaid = soldStay('2026-11-20', '2026-11-25', 0);

    $report = statisticsReport([], [$confirmed, $cancelled, $unpaid]);

    expect($report['totals']['revenue'])->toBe(50000)
        ->and($report['totals']['revenue_per_night'])->toBe(10000);
});

it('ajoute au revenu par nuit les montants saisis, avec les nuits de plateforme de leur mois', function () {
    $report = statisticsReport(
        [memoryIcalBlock(1, '2026-10-10', '2026-10-12'), memoryIcalBlock(1, '2026-09-10', '2026-09-15')],
        [soldStay('2026-11-02', '2026-11-04', 20000)],
        [memoryRevenue('airbnb', 1, '2026-10', 40000)],
    );

    // (20 000 + 40 000) / 4 nuits ; septembre, sans montant, n'entre pas.
    expect($report['totals']['revenue_per_night'])->toBe(15000)
        ->and($report['totals']['platform_revenue'])->toBe(['airbnb' => 40000, 'booking' => 0])
        ->and($report['months'][4]['platform_revenue'])->toBe(['airbnb' => 40000, 'booking' => 0])
        ->and($report['apartments'][0]['platform_revenue'])->toBe(40000);
});

it('signale les mois écoulés dont des nuits de plateforme n’ont pas de montant, pas le mois en cours', function () {
    $report = statisticsReport([memoryIcalBlock(1, '2026-10-10', '2026-10-12'), memoryIcalBlock(1, '2026-11-10', '2026-11-12')]);

    expect($report['months'][4]['platform_revenue_missing'])->toBeTrue()
        ->and($report['months'][5]['platform_revenue_missing'])->toBeFalse()
        ->and($report['totals']['revenue_per_night'])->toBeNull();
});

it('compte 0 € saisi comme un montant : le mois n’est plus incomplet', function () {
    $report = statisticsReport([memoryIcalBlock(1, '2026-10-10', '2026-10-12')], [], [memoryRevenue('airbnb', 1, '2026-10', 0)]);

    expect($report['months'][4]['platform_revenue_missing'])->toBeFalse()
        ->and($report['totals']['revenue_per_night'])->toBe(0);
});

it('ne compte ni comme vendues ni comme revenus manquants les dates fermées sur une plateforme', function () {
    $report = statisticsReport([memoryIcalBlock(1, '2026-10-10', '2026-10-20', summary: 'Airbnb (Not available)')]);

    expect($report['months'][4]['nights_platform'])->toBe(0)
        ->and($report['months'][4]['platform_revenue_missing'])->toBeFalse()
        ->and($report['months'][4]['occupancy'])->toBe(0.0)
        ->and($report['totals']['average_stay'])->toBeNull()
        ->and($report['totals']['revenue_per_night'])->toBeNull();
});

it('compte une date Booking.com comme un séjour vendu, ses nuits dans chaque mois qu’elle touche', function () {
    $report = statisticsReport(
        [memoryIcalBlock(1, '2026-05-28', '2026-06-03', 'booking', 'CLOSED - Not available')],
        [],
        [memoryRevenue('booking', 1, '2026-06', 20000)],
    );

    expect($report['months'][0]['month'])->toBe('2026-06')
        ->and($report['months'][0]['nights_platform'])->toBe(2)
        ->and($report['months'][0]['platform_revenue'])->toBe(['airbnb' => 0, 'booking' => 20000])
        ->and($report['totals']['revenue_per_night'])->toBe(10000);
});
