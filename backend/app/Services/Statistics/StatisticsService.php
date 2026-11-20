<?php

namespace App\Services\Statistics;

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Export\CsvWriter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Statistiques sur 12 mois glissants (spec « Back-office › Statistiques ») : nuits vendues
 * en direct et sur les plateformes, revenus directs, prix moyen par nuit, durée moyenne
 * de séjour, occupation, par mois et par logement.
 *
 * Revenus directs : total des réservations vendues hors taxe de séjour (reversée à la
 * commune), compté au mois d'arrivée. Les montants Airbnb ne sont pas connus (iCal ne
 * transmet que les dates). La note moyenne viendra avec les avis.
 */
class StatisticsService
{
    public const MONTHS = 12;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BookingRepositoryInterface $bookings,
        private readonly NightCounter $nights,
        private readonly RevenueCalculator $revenue,
        private readonly CsvWriter $csvWriter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(CarbonImmutable $today): array
    {
        $to = $today->startOfMonth()->addMonth();
        $from = $to->subMonths(self::MONTHS);
        $apartments = $this->apartments->allOrdered();
        // Seuls les logements existants comptent : sinon les nuits d'un logement supprimé
        // gonfleraient l'occupation sans compter parmi les nuits disponibles.
        $ids = $apartments->modelKeys();
        $blocks = $this->availability->planningBlocks($from->toDateString(), $to->toDateString())->whereIn('apartment_id', $ids);
        $sold = $this->bookings->soldArrivingBetween($from, $to)->whereIn('apartment_id', $ids);

        $months = [];
        for ($month = $from; $month->lt($to); $month = $month->addMonth()) {
            $end = $month->addMonth();
            $nights = $this->nights->count($blocks, $month, $end);
            $months[] = [
                'month' => $month->format('Y-m'),
                'nights_direct' => $nights['direct'],
                'nights_platform' => $nights['platform'],
                'revenue' => $this->revenue->of($sold->filter(fn (Booking $booking) => $booking->arrival->gte($month) && $booking->arrival->lt($end))),
                'occupancy' => $this->nights->occupancy($nights, $apartments->count(), (int) $month->diffInDays($end)),
            ];
        }

        $days = (int) $from->diffInDays($to);
        $total = $this->nights->count($blocks, $from, $to);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => [
                'revenue' => $this->revenue->of($sold),
                'nights_direct' => $total['direct'],
                'nights_platform' => $total['platform'],
                'average_nightly_price' => $this->averageNightlyPrice($sold),
                'average_stay' => $this->averageStay($blocks),
                'occupancy' => $this->nights->occupancy($total, $apartments->count(), $days),
            ],
            'months' => $months,
            'apartments' => $apartments->map(function (Apartment $apartment) use ($blocks, $sold, $from, $to, $days): array {
                $nights = $this->nights->count($blocks->where('apartment_id', $apartment->id), $from, $to);

                return [
                    'id' => $apartment->id,
                    'name' => $apartment->getTranslation('name', 'fr'),
                    'nights_direct' => $nights['direct'],
                    'nights_platform' => $nights['platform'],
                    'revenue' => $this->revenue->of($sold->where('apartment_id', $apartment->id)),
                    'occupancy' => $this->nights->occupancy($nights, 1, $days),
                    'rating' => null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Tableau mensuel et par logement, pour un tableur (séparateur « ; », montants en euros).
     */
    public function csv(CarbonImmutable $today): string
    {
        $report = $this->report($today);
        $euros = $this->csvWriter->euros(...);
        $rate = fn (?float $value) => $value === null ? '' : number_format($value, 1, ',', '');
        $rows = [['Mois', 'Nuits en direct', 'Nuits plateformes', 'Revenus directs (€)', 'Occupation (%)']];
        foreach ($report['months'] as $month) {
            $rows[] = [$month['month'], $month['nights_direct'], $month['nights_platform'], $euros($month['revenue']), $rate($month['occupancy'])];
        }
        $rows[] = [];
        $rows[] = ['Logement', 'Nuits en direct', 'Nuits plateformes', 'Revenus directs (€)', 'Occupation (%)'];
        foreach ($report['apartments'] as $apartment) {
            $rows[] = [$apartment['name'], $apartment['nights_direct'], $apartment['nights_platform'], $euros($apartment['revenue']), $rate($apartment['occupancy'])];
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
