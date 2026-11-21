<?php

namespace App\Services\Dashboard;

use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use App\Services\Ical\IcalImportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Situations à traiter, partagées par le tableau de bord et la barre du back-office :
 * conflits de dates à venir et calendriers actifs en échec, pour les logements existants
 * seulement (un logement supprimé ne laisse pas d'alerte impossible à traiter).
 */
class AlertCollector
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly IcalFeedRepositoryInterface $feeds,
    ) {}

    /**
     * @return Collection<int, AvailabilityBlock>
     */
    public function conflicts(CarbonImmutable $today): Collection
    {
        return $this->availability->upcomingConflicts($today->toDateString())
            ->whereIn('apartment_id', $this->apartmentIds())
            ->values();
    }

    /**
     * Calendriers actifs des logements existants.
     *
     * @return Collection<int, IcalFeed>
     */
    public function activeFeeds(): Collection
    {
        $ids = $this->apartmentIds();

        return $this->feeds->all()
            ->filter(fn (IcalFeed $feed) => $feed->is_active && in_array($feed->apartment_id, $ids, true))
            ->values();
    }

    /**
     * Calendriers en échec répété, ou arrivés vides et refusés par précaution.
     *
     * @return Collection<int, IcalFeed>
     */
    public function failingFeeds(): Collection
    {
        return $this->activeFeeds()
            ->filter(fn (IcalFeed $feed) => $feed->consecutive_failures >= IcalImportService::FAILURES_BEFORE_ALERT || $feed->empty_calendar_refused)
            ->values();
    }

    /**
     * @return list<int>
     */
    private function apartmentIds(): array
    {
        return $this->apartments->allOrdered()->modelKeys();
    }
}
