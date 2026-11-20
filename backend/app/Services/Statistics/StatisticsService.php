<?php

namespace App\Services\Statistics;

use App\Enums\BlockType;
use App\Enums\PayoutPlatform;
use App\Enums\StatisticsPeriod;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\PlatformPayout;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PlatformPayoutRepositoryInterface;
use App\Services\Export\CsvWriter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Statistiques sur 6 mois, 12 mois ou depuis le premier séjour (spec « Back-office ›
 * Statistiques ») : nuits vendues en direct et sur les plateformes, revenus par canal, part
 * du direct, revenu par nuit, durée moyenne de séjour, occupation, par mois et par logement.
 *
 * Revenus directs : argent encaissé moins les remboursements, hors taxe de séjour (voir
 * RevenueCalculator), compté au mois d'arrivée. Revenus des plateformes : montants nets importés de
 * leurs fichiers CSV (l'iCal ne transmet que les dates), au mois d'arrivée. La note moyenne
 * viendra avec les avis.
 */
class StatisticsService
{
    /** « Tout » : au plus cinq ans de mois. */
    public const MAX_MONTHS = 60;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PlatformPayoutRepositoryInterface $payouts,
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
        $apartments = $this->apartments->allOrdered();
        // Seuls les logements existants comptent : sinon les nuits d'un logement supprimé
        // gonfleraient l'occupation sans compter parmi les nuits disponibles.
        $ids = $apartments->modelKeys();
        $blocks = $this->availability->planningBlocks($from->toDateString(), $to->toDateString())->whereIn('apartment_id', $ids);
        $sold = $this->bookings->soldArrivingBetween($from, $to)->whereIn('apartment_id', $ids);
        $earning = $this->bookings->earningArrivingBetween($from, $to)->whereIn('apartment_id', $ids);
        $payouts = $this->payouts->arrivingBetween($from, $to)->whereIn('apartment_id', $ids);

        $months = [];
        for ($month = $from; $month->lt($to); $month = $month->addMonth()) {
            $end = $month->addMonth();
            $nights = $this->nights->count($blocks, $month, $end);
            $platform = $this->platformRevenue($payouts->filter(fn (PlatformPayout $payout) => $payout->arrival->gte($month) && $payout->arrival->lt($end)));
            $months[] = [
                'month' => $month->format('Y-m'),
                'nights_direct' => $nights['direct'],
                'nights_platform' => $nights['platform'],
                'revenue' => $this->revenue->of($earning->filter(fn (Booking $booking) => $booking->arrival->gte($month) && $booking->arrival->lt($end))),
                'platform_revenue' => $platform,
                // Séjours arrivés ce mois-ci sur une plateforme dont aucun revenu n'est importé :
                // montants inconnus (revenus et séjours comptés tous deux au mois d'arrivée).
                'platform_revenue_missing' => $this->platformRevenueMissing($this->nights->platformNightsArriving($blocks, $month, $end), $platform),
                'occupancy' => $this->nights->occupancy($nights, $apartments->count(), (int) $month->diffInDays($end)),
            ];
        }

        $days = (int) $from->diffInDays($to);
        $total = $this->nights->count($blocks, $from, $to);
        $direct = $this->revenue->of($earning);
        $platform = $this->platformRevenue($payouts);
        $allRevenue = $direct + array_sum($platform);
        $soldNights = $total['direct'] + $total['platform'];
        $platformNights = $this->nights->platformNightsArriving($blocks, $from, $to);
        $missing = $this->platformRevenueMissing($platformNights, $platform);
        // Revenu par nuit : seulement les plateformes dont les nuits sont comptées (calendrier
        // iCal relié) ; sinon leurs revenus s'ajouteraient sans leurs nuits.
        $countedRevenue = $direct + array_sum(array_intersect_key($platform, array_filter($platformNights)));

        return [
            'period' => $period->value,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => [
                'revenue' => $direct,
                'platform_revenue' => $platform,
                'total_revenue' => $allRevenue,
                'direct_share' => $allRevenue > 0 ? round($direct / $allRevenue * 100, 1) : null,
                // Revenu par nuit vendue, seulement si chaque plateforme vendue a ses revenus importés.
                'revenue_per_night' => $soldNights > 0 && ! $missing ? (int) round($countedRevenue / $soldNights) : null,
                'nights_direct' => $total['direct'],
                'nights_platform' => $total['platform'],
                'average_nightly_price' => $this->averageNightlyPrice($sold),
                'average_stay' => $this->averageStay($blocks),
                'occupancy' => $this->nights->occupancy($total, $apartments->count(), $days),
            ],
            'months' => $months,
            'apartments' => $apartments->map(function (Apartment $apartment) use ($blocks, $earning, $payouts, $from, $to, $days): array {
                $nights = $this->nights->count($blocks->where('apartment_id', $apartment->id), $from, $to);

                return [
                    'id' => $apartment->id,
                    'name' => $apartment->getTranslation('name', 'fr'),
                    'nights_direct' => $nights['direct'],
                    'nights_platform' => $nights['platform'],
                    'revenue' => $this->revenue->of($earning->where('apartment_id', $apartment->id)),
                    'platform_revenue' => array_sum($this->platformRevenue($payouts->where('apartment_id', $apartment->id))),
                    'occupancy' => $this->nights->occupancy($nights, 1, $days),
                    'rating' => null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Premier mois de la période. « Tout » : depuis le premier séjour, au plus MAX_MONTHS mois,
     * et au moins le mois en cours (un premier séjour à venir ne donne pas une période inversée).
     */
    private function start(StatisticsPeriod $period, CarbonImmutable $to): CarbonImmutable
    {
        $months = $period->months();
        if ($months !== null) {
            return $to->subMonths($months);
        }
        $earliest = $this->availability->earliestStart();
        $first = $earliest === null ? $to->subMonth() : CarbonImmutable::parse($earliest)->startOfMonth();

        return $first->min($to->subMonth())->max($to->subMonths(self::MAX_MONTHS));
    }

    /**
     * Vrai si une plateforme a vendu des nuits sans qu'aucun de ses revenus ne soit importé
     * (une plateforme sans import de revenus compte comme manquante).
     *
     * @param  array<string, int>  $nights  nuits par plateforme
     * @param  array<string, int>  $revenue  revenus par plateforme
     */
    private function platformRevenueMissing(array $nights, array $revenue): bool
    {
        foreach ($nights as $platform => $count) {
            if ($count > 0 && ($revenue[$platform] ?? 0) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Revenus nets versés par chaque plateforme, en centimes.
     *
     * @param  Collection<int, PlatformPayout>  $payouts
     * @return array<string, int>
     */
    private function platformRevenue(Collection $payouts): array
    {
        $revenue = [];
        foreach (PayoutPlatform::cases() as $platform) {
            $revenue[$platform->value] = (int) $payouts->filter(fn (PlatformPayout $payout) => $payout->platform === $platform)->sum('net_amount');
        }

        return $revenue;
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
     * Prix moyen d'une nuit vendue en direct (hébergement seul), en centimes.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    private function averageNightlyPrice(Collection $bookings): ?int
    {
        $nights = $bookings->sum(fn (Booking $booking) => $booking->nights());

        return $nights > 0 ? (int) round($bookings->sum('accommodation_amount') / $nights) : null;
    }

    /**
     * Durée moyenne d'un séjour vendu (direct et plateformes), en nuits.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     */
    private function averageStay(Collection $blocks): ?float
    {
        $stays = $blocks->filter(fn (AvailabilityBlock $block) => $block->type !== BlockType::Manual && $this->nights->kind($block) !== null);

        return $stays->isEmpty() ? null : round($stays->avg(fn (AvailabilityBlock $block) => $block->starts_on->diffInDays($block->ends_on)), 1);
    }
}
