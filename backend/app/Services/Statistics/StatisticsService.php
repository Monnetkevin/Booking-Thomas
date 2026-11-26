<?php

namespace App\Services\Statistics;

use App\Enums\BookingStatus;
use App\Enums\StatisticsPeriod;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PlatformRevenueRepositoryInterface;
use App\Services\Export\CsvWriter;
use App\Services\PlatformRevenue\PlatformRevenueLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Statistiques sur 6 mois, 12 mois ou depuis le premier séjour (spec « Back-office ›
 * Statistiques ») : nuits vendues en direct et sur les plateformes, revenus par canal, part
 * du direct, revenu par nuit, durée moyenne de séjour, occupation, par mois et par logement.
 *
 * Revenus directs : argent encaissé moins les remboursements, hors taxe de séjour (voir
 * RevenueCalculator), compté au mois d'arrivée. Revenus des plateformes : net versé saisi à la
 * main chaque mois par logement (PlatformRevenueService ; l'iCal ne transmet que les dates),
 * compté au mois saisi. La note moyenne viendra avec les avis.
 */
class StatisticsService
{
    /** « Tout » : au plus cinq ans de mois. */
    public const MAX_MONTHS = 60;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly SoldStayBlocks $soldStays,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PlatformRevenueRepositoryInterface $platformRevenues,
        private readonly PlatformRevenueLedger $ledger,
        private readonly NightCounter $nights,
        private readonly RevenueCalculator $revenue,
        private readonly CsvWriter $csvWriter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(CarbonImmutable $today, StatisticsPeriod $period = StatisticsPeriod::Year): array
    {
        $to = $today->startOfMonth()->addMonth();
        $from = $this->start($period, $to);
        // Logements non supprimés : leurs revenus et séjours comptent même s'ils ne sont plus
        // publiés. L'occupation, elle, ne retient que ceux sur le marché (marketOccupancy).
        $apartments = $this->apartments->allOrdered();
        $ids = $apartments->modelKeys();
        $blocks = $this->soldStays->between($from, $to, $ids);
        $earning = $this->bookings->earningArrivingBetween($from, $to)->whereIn('apartment_id', $ids);
        $ledger = $this->ledger->months($blocks, $this->platformRevenues->between($from, $to), $from, $to, $ids);
        // Le mois en cours n'est pas encore versé : ses montants ne manquent pas.
        $closed = $today->startOfMonth();

        $months = [];
        for ($month = $from; $month->lt($to); $month = $month->addMonth()) {
            $end = $month->addMonth();
            $nights = $this->nights->count($blocks, $month, $end);
            $cells = $ledger[$month->format('Y-m')];
            $months[] = [
                'month' => $month->format('Y-m'),
                'nights_direct' => $nights['direct'],
                'nights_platform' => $nights['platform'],
                'revenue' => $this->revenue->of($earning->filter(fn (Booking $booking) => $booking->arrival->gte($month) && $booking->arrival->lt($end))),
                'platform_revenue' => $this->ledger->totals($cells),
                // Nuits vendues ce mois-ci sur une plateforme sans montant saisi (mois écoulés seulement).
                'platform_revenue_missing' => $month->lt($closed) && $this->ledger->missing($cells) > 0,
                'occupancy' => $this->nights->marketOccupancy($blocks, $apartments, $month, $end),
            ];
        }

        $total = $this->nights->count($blocks, $from, $to);
        $direct = $this->revenue->of($earning);
        $cells = $this->mergeMonths($ledger);
        $platform = $this->ledger->totals($cells);
        $allRevenue = $direct + array_sum($platform);

        return [
            'period' => $period->value,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => [
                'revenue' => $direct,
                'platform_revenue' => $platform,
                'total_revenue' => $allRevenue,
                'direct_share' => $allRevenue > 0 ? round($direct / $allRevenue * 100, 1) : null,
                'revenue_per_night' => $this->revenuePerNight($earning, $ledger),
                'nights_direct' => $total['direct'],
                'nights_platform' => $total['platform'],
                'average_stay' => $this->averageStay($blocks),
                'occupancy' => $this->nights->marketOccupancy($blocks, $apartments, $from, $to),
            ],
            'months' => $months,
            'apartments' => $apartments->map(function (Apartment $apartment) use ($blocks, $earning, $cells, $from, $to): array {
                $own = $blocks->where('apartment_id', $apartment->id);
                $nights = $this->nights->count($own, $from, $to);

                return [
                    'id' => $apartment->id,
                    'name' => $apartment->getTranslation('name', 'fr'),
                    'nights_direct' => $nights['direct'],
                    'nights_platform' => $nights['platform'],
                    'revenue' => $this->revenue->of($earning->where('apartment_id', $apartment->id)),
                    'platform_revenue' => array_sum($this->ledger->totals([$apartment->id => $cells[$apartment->id] ?? []])),
                    'occupancy' => $this->nights->marketOccupancy($own, collect([$apartment]), $from, $to),
                    'rating' => null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Premier mois de la période. « Tout » : depuis le premier séjour ou le premier revenu de
     * plateforme saisi (les calendriers oublient les vieux séjours), au plus MAX_MONTHS mois, et
     * au moins le mois en cours (un premier séjour à venir ne donne pas une période inversée).
     */
    private function start(StatisticsPeriod $period, CarbonImmutable $to): CarbonImmutable
    {
        $months = $period->months();
        if ($months !== null) {
            return $to->subMonths($months);
        }
        $starts = array_filter([$this->availability->earliestStart(), $this->platformRevenues->earliestMonth()]);
        $first = $starts === [] ? $to->subMonth() : CarbonImmutable::parse(min($starts))->startOfMonth();

        return $first->min($to->subMonth())->max($to->subMonths(self::MAX_MONTHS));
    }

    /**
     * Cases de plusieurs mois réunies par logement et plateforme (nuits et montants additionnés ;
     * montant null si aucun mois n'en a).
     *
     * @param  array<string, array<int, array<string, array{nights: int, net_amount: int|null}>>>  $ledger
     * @return array<int, array<string, array{nights: int, net_amount: int|null}>>
     */
    private function mergeMonths(array $ledger): array
    {
        $merged = [];
        foreach ($ledger as $cells) {
            foreach ($cells as $apartmentId => $platforms) {
                foreach ($platforms as $platform => $cell) {
                    $previous = $merged[$apartmentId][$platform] ?? ['nights' => 0, 'net_amount' => null];
                    $merged[$apartmentId][$platform] = [
                        'nights' => $previous['nights'] + $cell['nights'],
                        'net_amount' => $cell['net_amount'] === null ? $previous['net_amount'] : ($previous['net_amount'] ?? 0) + $cell['net_amount'],
                    ];
                }
            }
        }

        return $merged;
    }

    /**
     * Revenu par nuit vendue, revenus et nuits pris sur les mêmes séjours : réservations
     * directes arrivées dans la période, vendues et payées (sans les annulations ni les saisies
     * non encore payées) ; mois de plateforme qui ont à la fois des nuits et un montant saisi
     * (sinon des nuits compteraient sans leurs revenus, ou l'inverse). Null sans aucune nuit.
     *
     * @param  Collection<int, Booking>  $earning
     * @param  array<string, array<int, array<string, array{nights: int, net_amount: int|null}>>>  $ledger
     */
    private function revenuePerNight(Collection $earning, array $ledger): ?int
    {
        $stays = $earning->filter(fn (Booking $booking) => in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)
            && $this->revenue->ofBooking($booking) > 0);
        $nights = $stays->sum(fn (Booking $booking) => $booking->nights());
        $revenue = $this->revenue->of($stays);
        foreach ($ledger as $cells) {
            foreach ($cells as $platforms) {
                foreach ($platforms as $cell) {
                    if ($cell['net_amount'] !== null && $cell['nights'] > 0) {
                        $nights += $cell['nights'];
                        $revenue += $cell['net_amount'];
                    }
                }
            }
        }

        return $nights > 0 ? (int) round($revenue / $nights) : null;
    }

    /**
     * Tableau mensuel et par logement, pour un tableur (séparateur « ; », montants en euros).
     */
    public function csv(CarbonImmutable $today, StatisticsPeriod $period = StatisticsPeriod::Year): string
    {
        $report = $this->report($today, $period);
        $euros = $this->csvWriter->euros(...);
        $rate = fn (?float $value) => $value === null ? '' : number_format($value, 1, ',', '');
        $rows = [['Mois', 'Nuits en direct', 'Nuits plateformes', 'Revenus directs (€)', 'Revenus plateformes (€)', 'Occupation (%)']];
        foreach ($report['months'] as $month) {
            $rows[] = [$month['month'], $month['nights_direct'], $month['nights_platform'], $euros($month['revenue']), $euros(array_sum($month['platform_revenue'])), $rate($month['occupancy'])];
        }
        $rows[] = [];
        $rows[] = ['Logement', 'Nuits en direct', 'Nuits plateformes', 'Revenus directs (€)', 'Revenus plateformes (€)', 'Occupation (%)'];
        foreach ($report['apartments'] as $apartment) {
            $rows[] = [$apartment['name'], $apartment['nights_direct'], $apartment['nights_platform'], $euros($apartment['revenue']), $euros($apartment['platform_revenue']), $rate($apartment['occupancy'])];
        }

        return $this->csvWriter->write($rows);
    }

    /**
     * Durée moyenne d'un séjour vendu (direct et plateformes), en nuits.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     */
    private function averageStay(Collection $blocks): ?float
    {
        $stays = $blocks->filter(fn (AvailabilityBlock $block) => in_array($this->nights->kind($block), ['direct', 'platform'], true));

        return $stays->isEmpty() ? null : round($stays->avg(fn (AvailabilityBlock $block) => $block->starts_on->diffInDays($block->ends_on)), 1);
    }
}
