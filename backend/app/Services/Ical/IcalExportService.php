<?php

namespace App\Services\Ical;

use App\Models\AvailabilityBlock;
use App\Models\IcalFeed;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use App\Support\SiteUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

/**
 * Calendrier du site publié pour une plateforme (URL à token secret) : nuits occupées
 * par les réservations directes, les blocages manuels et les autres plateformes, sans
 * les événements importés de cette plateforme (évite une boucle). Aucune donnée
 * personnelle : chaque séjour s'appelle « Réservé ».
 */
class IcalExportService
{
    public function __construct(
        private readonly IcalFeedRepositoryInterface $feeds,
        private readonly AvailabilityRepositoryInterface $availability,
    ) {}

    public function feedForToken(string $token): ?IcalFeed
    {
        return $this->feeds->findByExportToken($token);
    }

    public function calendar(IcalFeed $feed): string
    {
        $today = CarbonImmutable::instance(Date::today());
        $host = SiteUrl::host();

        $events = $this->availability->exportable($feed->apartment_id, $feed->id, $today->toDateString())
            ->map(fn (AvailabilityBlock $block) => Event::create('Réservé')
                ->uniqueIdentifier("block-{$block->id}@$host")
                ->createdAt($today)
                ->startsAt($block->starts_on)
                ->endsAt($block->ends_on)
                ->fullDay())
            ->all();

        return Calendar::create((string) config('app.name'))
            ->productIdentifier('-//'.config('app.name').'//Calendrier des logements//FR')
            ->withoutTimezone()
            ->event($events)
            ->get();
    }
}
