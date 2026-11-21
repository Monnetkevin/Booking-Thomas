<?php

namespace App\Services\Dashboard;

use App\Enums\BlockType;
use App\Enums\DashboardPeriod;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\IcalFeed;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Services\Statistics\NightCounter;
use App\Services\Statistics\RevenueCalculator;
use App\Services\Statistics\SoldStayBlocks;
use Carbon\CarbonImmutable;

/**
 * Tableau de bord de l'hôte (spec « Back-office › Tableau de bord ») : chiffres de la
 * semaine, du mois ou de l'année comparés à la période précédente, revenus directs des six
 * derniers mois, arrivées et départs des 7 prochains jours, alertes (conflits de dates,
 * calendriers en échec ou vides refusés, messages non lus). Les réservations à valider et
 * les paiements échoués s'y ajoutent avec le paiement Stripe.
 */
class DashboardService
{
    public const MOVEMENT_DAYS = 7;

    public const REVENUE_MONTHS = 6;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BookingRepositoryInterface $bookings,
        private readonly AlertCollector $alertCollector,
        private readonly ContactMessageRepositoryInterface $messages,
        private readonly NightCounter $nights,
        private readonly RevenueCalculator $revenue,
        private readonly SoldStayBlocks $soldStays,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(CarbonImmutable $today, DashboardPeriod $period = DashboardPeriod::Month): array
    {
        [$from, $to] = $period->current($today);

        return [
            'today' => $today->toDateString(),
            'period' => ['key' => $period->value, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'figures' => $this->figures($period, $today),
            'revenue_months' => $this->revenueMonths($today),
            'movements' => $this->movements($today),
            'alerts' => $this->alerts($today),
        ];
    }

    /**
     * Arrivées et départs jour par jour (séjours vendus, pas les fermetures).
     *
     * @return list<array{date: string, arrivals: list<array<string, mixed>>, departures: list<array<string, mixed>>}>
     */
    private function movements(CarbonImmutable $today): array
    {
        $end = $today->addDays(self::MOVEMENT_DAYS);
        $apartments = $this->apartments->allOrdered()->keyBy('id');
        // Blocs bruts : un séjour Booking.com dont le versement n'est pas encore importé reste une
        // arrivée à préparer ; seule une fermeture posée sur des nuits déjà fermées est écartée.
        $blocks = $this->availability->planningBlocks($today->subDay()->toDateString(), $end->toDateString())
            ->whereIn('apartment_id', $apartments->keys()->all())
            ->reject(fn (AvailabilityBlock $block) => $block->isRedundantClosure())
            ->filter(fn (AvailabilityBlock $block) => in_array($this->nights->kind($block), ['direct', 'platform'], true));

        $days = [];
        for ($day = $today; $day->lt($end); $day = $day->addDay()) {
            $describe = fn (AvailabilityBlock $block) => [
                'apartment' => $apartments->get($block->apartment_id)?->getTranslation('name', 'fr'),
                'label' => $block->type === BlockType::Booking ? $block->booking?->guest_name : $block->icalFeed?->provider->label(),
                'reference' => $block->booking?->reference,
                'guests' => $block->booking ? $block->booking->adults + $block->booking->children : null,
            ];
            $days[] = [
                'date' => $day->toDateString(),
                'arrivals' => $blocks->filter(fn (AvailabilityBlock $block) => $block->starts_on->isSameDay($day))->map($describe)->values()->all(),
                'departures' => $blocks->filter(fn (AvailabilityBlock $block) => $block->ends_on->isSameDay($day))->map($describe)->values()->all(),
            ];
        }

        return $days;
    }

    /**
     * Chiffres de la période et de la précédente (comparaison) : occupation tous canaux,
     * revenus directs (au jour d'arrivée, hors taxe de séjour), séjours arrivés dont directs.
     *
     * @return array<string, array<string, mixed>>
     */
    private function figures(DashboardPeriod $period, CarbonImmutable $today): array
    {
        $current = $this->periodFigures(...$period->current($today));
        $previous = $this->periodFigures(...$period->previous($today));

        return [
            'occupancy' => ['value' => $current['occupancy'], 'previous' => $previous['occupancy']],
            'revenue' => ['value' => $current['revenue'], 'previous' => $previous['revenue']],
            'stays' => ['value' => $current['stays'], 'direct' => $current['direct_stays'], 'previous' => $previous['stays']],
        ];
    }

    /**
     * @return array{occupancy: float|null, revenue: int, stays: int, direct_stays: int}
     */
    private function periodFigures(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $apartments = $this->apartments->allOrdered();
        $ids = $apartments->modelKeys();
        $blocks = $this->soldStays->between($from, $to, $ids);
        $nights = $this->nights->count($blocks, $from, $to);
        $arrivals = $this->nights->arrivals($blocks, $from, $to);

        return [
            'occupancy' => $this->nights->occupancy($nights, $apartments->count(), (int) $from->diffInDays($to)),
            'revenue' => $this->revenue->of($this->bookings->earningArrivingBetween($from, $to)->whereIn('apartment_id', $ids)),
            'stays' => $arrivals['all'],
            'direct_stays' => $arrivals['direct'],
        ];
    }

    /**
     * Revenus directs des six derniers mois (mois en cours compris), au mois d'arrivée.
     *
     * @return list<array{month: string, revenue: int}>
     */
    private function revenueMonths(CarbonImmutable $today): array
    {
        $to = $today->startOfMonth()->addMonth();
        $from = $to->subMonths(self::REVENUE_MONTHS);
        $ids = $this->apartments->allOrdered()->modelKeys();
        $earning = $this->bookings->earningArrivingBetween($from, $to)->whereIn('apartment_id', $ids);

        $months = [];
        for ($month = $from; $month->lt($to); $month = $month->addMonth()) {
            $end = $month->addMonth();
            $months[] = [
                'month' => $month->format('Y-m'),
                'revenue' => $this->revenue->of($earning->filter(fn (Booking $booking) => $booking->arrival->gte($month) && $booking->arrival->lt($end))),
            ];
        }

        return $months;
    }

    /**
     * @return array<string, mixed>
     */
    private function alerts(CarbonImmutable $today): array
    {
        return [
            'conflicts' => $this->alertCollector->conflicts($today)->map(fn (AvailabilityBlock $block) => [
                'apartment' => $block->apartment->getTranslation('name', 'fr'),
                'starts_on' => $block->starts_on->toDateString(),
                'ends_on' => $block->ends_on->toDateString(),
            ])->all(),
            'failing_feeds' => $this->alertCollector->failingFeeds()->map(fn (IcalFeed $feed) => [
                'apartment' => $feed->apartment->getTranslation('name', 'fr'),
                'provider' => $feed->provider->label(),
                'empty_refused' => $feed->empty_calendar_refused,
                'failures' => $feed->consecutive_failures,
            ])->all(),
            'unread_messages' => $this->messages->unreadCount(),
        ];
    }
}
