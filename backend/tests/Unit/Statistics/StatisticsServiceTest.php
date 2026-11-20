<?php

use App\Enums\BookingStatus;
use App\Enums\StatisticsPeriod;
use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Export\CsvWriter;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\RevenueCalculator;
use App\Services\Statistics\StatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Rapport de novembre 2026 (période de 6 mois) sur un logement, à partir de blocs,
 * réservations et versements en mémoire. Renvoie aussi la date de début demandée aux versements.
 *
 * @return array{0: array<string, mixed>, 1: list<string>}
 */
function statisticsReport(array $blocks, array $earning = [], array $payouts = []): array
{
    $apartments = Mockery::mock(ApartmentRepositoryInterface::class);
    $apartments->allows('allOrdered')->andReturn(new Collection([(new Apartment)->forceFill(['id' => 1])->setTranslation('name', 'fr', 'Le Cocon')]));
    $availability = Mockery::mock(AvailabilityRepositoryInterface::class);
    $availability->allows('planningBlocks')->andReturn(new Collection($blocks));
    $bookings = Mockery::mock(BookingRepositoryInterface::class);
    $bookings->allows('earningArrivingBetween')->andReturn(new Collection($earning));
    $bookings->allows('soldArrivingBetween')->andReturn(new Collection(array_filter($earning, fn ($booking) => $booking->status !== BookingStatus::Cancelled)));
    $repository = Mockery::mock(PlatformPayoutRepositoryInterface::class);
    $asked = [];
    $repository->allows('arrivingBetween')->andReturnUsing(function ($from) use ($payouts, &$asked) {
        $asked[] = $from->toDateString();

        return new Collection($payouts);
    });

    $service = new StatisticsService($apartments, $availability, $bookings, $repository, new NightCounter, new RevenueCalculator, new CsvWriter);

    return [$service->report(CarbonImmutable::parse('2026-11-20'), StatisticsPeriod::SixMonths), $asked];
}

function soldStay(string $arrival, string $departure, int $paid, BookingStatus $status = BookingStatus::Confirmed, int $refunded = 0): Booking
{
    return memoryBooking(1, $arrival, $departure, $status, ['paid_amount' => $paid, 'refunded_amount' => $refunded, 'accommodation_amount' => $paid]);
}

it('calcule le revenu par nuit sur les séjours vendus et payés, sans annulation ni saisie non payée', function () {
    $confirmed = soldStay('2026-11-02', '2026-11-04', 20000);
    $cancelled = soldStay('2026-11-10', '2026-11-14', 30000, BookingStatus::Cancelled);
    $unpaid = soldStay('2026-11-20', '2026-11-25', 0);

    [$report] = statisticsReport([], [$confirmed, $cancelled, $unpaid]);

    expect($report['totals']['revenue'])->toBe(50000)
        ->and($report['totals']['revenue_per_night'])->toBe(10000);
});

it('ajoute au revenu par nuit les plateformes dont le calendrier est relié, nuits au mois d’arrivée', function () {
    [$report] = statisticsReport(
        [memoryIcalBlock(1, '2026-11-10', '2026-11-12')],
        [soldStay('2026-11-02', '2026-11-04', 20000)],
        [memoryPayout('airbnb', 1, '2026-11-10', 2, 40000), memoryPayout('booking', 1, '2026-11-15', 2, 99999)],
    );

    // (20 000 + 40 000) / 4 nuits ; Booking.com sans calendrier ni séjour confirmé n'entre pas.
    expect($report['totals']['revenue_per_night'])->toBe(15000);
});

it('ne donne pas de revenu par nuit quand une plateforme a vendu sans revenus importés', function () {
    [$report] = statisticsReport([memoryIcalBlock(1, '2026-11-10', '2026-11-12')], [soldStay('2026-11-02', '2026-11-04', 20000)]);

    expect($report['totals']['revenue_per_night'])->toBeNull()
        ->and($report['months'][5]['platform_revenue_missing'])->toBeTrue();
});

it('ne compte ni comme vendues ni comme revenus manquants les dates fermées sur une plateforme', function () {
    [$report] = statisticsReport([
        memoryIcalBlock(1, '2026-11-10', '2026-11-20', summary: 'Airbnb (Not available)'),
        memoryIcalBlock(1, '2026-11-20', '2026-11-30', 'booking', 'CLOSED - Not available'),
    ]);

    expect($report['months'][5]['nights_platform'])->toBe(0)
        ->and($report['months'][5]['platform_revenue_missing'])->toBeFalse()
        ->and($report['months'][5]['occupancy'])->toBe(0.0)
        ->and($report['totals']['average_stay'])->toBeNull()
        ->and($report['totals']['revenue_per_night'])->toBeNull();
});

it('confirme un séjour Booking.com arrivé avant la période par un versement de cette date', function () {
    [$report, $asked] = statisticsReport(
        [memoryIcalBlock(1, '2026-05-28', '2026-06-03', 'booking', 'CLOSED - Not available')],
        [],
        [memoryPayout('booking', 1, '2026-05-28', 6, 60000)],
    );

    expect($asked)->toBe(['2026-05-28'])
        ->and($report['months'][0]['month'])->toBe('2026-06')
        ->and($report['months'][0]['nights_platform'])->toBe(2)
        // Revenu compté au mois d'arrivée, hors période.
        ->and($report['totals']['platform_revenue'])->toBe(['airbnb' => 0, 'booking' => 0]);
});
