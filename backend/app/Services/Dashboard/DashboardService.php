<?php

namespace App\Services\Dashboard;

use App\Enums\BlockType;
use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use App\Services\Ical\IcalImportService;
use App\Services\Statistics\NightCounter;
use Carbon\CarbonImmutable;

/**
 * Tableau de bord de l'hôte (spec « Back-office › Tableau de bord ») : arrivées et départs
 * des 7 prochains jours, occupation et encaissements du mois, alertes (conflits de dates,
 * calendriers en échec ou vides refusés, messages non lus). Les réservations à valider et
 * les paiements échoués s'y ajoutent avec le paiement Stripe.
 */
class DashboardService
{
    public const MOVEMENT_DAYS = 7;

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly BookingRepositoryInterface $bookings,
        private readonly IcalFeedRepositoryInterface $feeds,
        private readonly ContactMessageRepositoryInterface $messages,
        private readonly NightCounter $nights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(CarbonImmutable $today): array
    {
        return [
            'today' => $today->toDateString(),
            'movements' => $this->movements($today),
            'month' => $this->month($today),
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
        $blocks = $this->availability->planningBlocks($today->subDay()->toDateString(), $end->toDateString())
            ->whereIn('apartment_id', $apartments->keys()->all())
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
     * @return array{occupancy: float|null, nights: array{direct: int, platform: int, closed: int}, revenue: int}
     */
    private function month(CarbonImmutable $today): array
    {
        $from = $today->startOfMonth();
        $to = $from->addMonth();
        $apartments = $this->apartments->allOrdered();
        $blocks = $this->availability->planningBlocks($from->toDateString(), $to->toDateString())->whereIn('apartment_id', $apartments->modelKeys());
        $nights = $this->nights->count($blocks, $from, $to);

        return [
            'occupancy' => $this->nights->occupancy($nights, $apartments->count(), (int) $from->diffInDays($to)),
            'nights' => $nights,
            'revenue' => $this->bookings->netPaymentsBetween($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function alerts(CarbonImmutable $today): array
    {
        $feeds = $this->feeds->all()->filter(fn (IcalFeed $feed) => $feed->is_active);

        return [
            'conflicts' => $this->availability->upcomingConflicts($today->toDateString())->map(fn (AvailabilityBlock $block) => [
                'apartment' => $block->apartment->getTranslation('name', 'fr'),
                'starts_on' => $block->starts_on->toDateString(),
                'ends_on' => $block->ends_on->toDateString(),
            ])->values()->all(),
            'failing_feeds' => $feeds
                ->filter(fn (IcalFeed $feed) => $feed->consecutive_failures >= IcalImportService::FAILURES_BEFORE_ALERT || $feed->empty_calendar_refused)
                ->map(fn (IcalFeed $feed) => [
                    'apartment' => $feed->apartment->getTranslation('name', 'fr'),
                    'provider' => $feed->provider->label(),
                    'empty_refused' => $feed->empty_calendar_refused,
                    'failures' => $feed->consecutive_failures,
                ])->values()->all(),
            'unread_messages' => $this->messages->unreadCount(),
        ];
    }
}
