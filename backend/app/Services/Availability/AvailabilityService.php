<?php

namespace App\Services\Availability;

use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Models\RatePeriod;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Booking\Stay;
use App\Services\Booking\StayRules;
use App\Services\Catalog\CatalogCache;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Disponibilités pour le site public : calendrier d'un logement et recherche par dates.
 * Le calendrier n'est qu'une aide à la saisie ; le devis revérifie toujours tout.
 */
class AvailabilityService
{
    /** Fenêtre maximale d'un calendrier (un peu plus que l'horizon de réservation). */
    public const MAX_WINDOW_DAYS = 400;

    public function __construct(
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly RatePeriodRepositoryInterface $periods,
        private readonly SettingRepositoryInterface $settings,
        private readonly StayRules $rules,
        private readonly PriceCalculator $calculator,
        private readonly Cache $cache,
    ) {}

    /**
     * Nuits qu'aucun séjour ne peut contenir, règles par période et bornes de réservation.
     * Mis en cache une minute ; vidé avec le catalogue ou par flushApartment().
     *
     * @return array<string, mixed>
     */
    public function calendar(Apartment $apartment, ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::instance(Date::today());
        $start = $from ? CarbonImmutable::parse($from) : $today;
        $end = $to ? CarbonImmutable::parse($to) : $today->addMonths((int) $this->settings->get('booking.horizon_months', 12))->addDay();
        if ($start->diffInDays($end) > self::MAX_WINDOW_DAYS) {
            $end = $start->addDays(self::MAX_WINDOW_DAYS);
        }

        $key = "availability:{$apartment->id}:{$start->toDateString()}:{$end->toDateString()}";

        return $this->cache->tags([CatalogCache::TAG, self::tag($apartment->id)])
            ->remember($key, CatalogCache::TTL_SECONDS, fn () => $this->buildCalendar($apartment, $start, $end, $today));
    }

    /** À appeler quand les nuits occupées d'un logement changent (réservation, iCal, blocage). */
    public function flushApartment(int $apartmentId): void
    {
        $this->cache->tags([self::tag($apartmentId)])->flush();
    }

    /**
     * Logements publiés pour ce séjour : disponibles (avec leur prix) ou non (avec la raison).
     *
     * @param  Collection<int, Apartment>  $apartments
     * @return Collection<int, array{apartment: Apartment, available: bool, quote: Quote|null, reason: string|null}>
     */
    public function search(Collection $apartments, Stay $stay): Collection
    {
        return $apartments
            ->map(function (Apartment $apartment) use ($stay) {
                $violations = $this->rules->violations($apartment, $stay);

                return [
                    'apartment' => $apartment,
                    'available' => $violations === [],
                    'quote' => $violations === [] ? $this->calculator->calculate($apartment, $stay) : null,
                    'reason' => $violations[0]['message'] ?? null,
                ];
            })
            // Disponibles d'abord, dans l'ordre d'affichage choisi par l'hôte.
            ->sortBy(fn (array $result) => $result['available'] ? 0 : 1)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCalendar(Apartment $apartment, CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $today): array
    {
        $turnover = $apartment->turnover_days;
        $unavailable = [];

        // Nuits occupées, élargies du battement de ménage avant et après chaque bloc.
        $blocks = $this->availability->activeBlocks(
            $apartment->id,
            $start->subDays($turnover)->toDateString(),
            $end->addDays($turnover)->toDateString(),
        );
        foreach ($blocks as $block) {
            /** @var AvailabilityBlock $block */
            for ($night = $block->starts_on->subDays($turnover); $night->lt($block->ends_on->addDays($turnover)); $night = $night->addDay()) {
                $unavailable[$night->toDateString()] = true;
            }
        }

        // Nuits sans tarif : pas encore ouvertes à la réservation.
        $periods = $this->periods->covering($apartment->id, $start->toDateString(), $end->toDateString());
        for ($night = $start; $night->lt($end); $night = $night->addDay()) {
            $covered = $periods->contains(fn (RatePeriod $p) => $night->betweenIncluded($p->starts_on, $p->ends_on));
            if (! $covered) {
                $unavailable[$night->toDateString()] = true;
            }
        }

        $dates = array_keys($unavailable);
        $dates = array_values(array_filter($dates, fn (string $date) => $date >= $start->toDateString() && $date < $end->toDateString()));
        sort($dates);

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'first_arrival' => $today->addDays((int) $this->settings->get('booking.min_notice_days', 1))->toDateString(),
            'last_arrival' => $today->addMonths((int) $this->settings->get('booking.horizon_months', 12))->toDateString(),
            'min_stay' => $apartment->min_stay,
            'max_stay' => $apartment->max_stay,
            'unavailable' => $dates,
            'periods' => $periods->map(fn (RatePeriod $period) => [
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'min_stay' => $period->min_stay ?? $apartment->min_stay,
                'arrival_days' => $period->arrival_days,
            ])->values()->all(),
        ];
    }

    private static function tag(int $apartmentId): string
    {
        return "availability:$apartmentId";
    }
}
