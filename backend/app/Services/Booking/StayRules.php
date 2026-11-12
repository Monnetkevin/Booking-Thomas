<?php

namespace App\Services\Booking;

use App\Enums\ApartmentStatus;
use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Contrôles avant d'accepter un séjour (spec « Disponibilités et tarification »).
 * Renvoie la liste des règles non respectées : vide = séjour possible.
 */
class StayRules
{
    public function __construct(
        private readonly RatePeriodRepositoryInterface $periods,
        private readonly AvailabilityRepositoryInterface $availability,
        private readonly SettingRepositoryInterface $settings,
    ) {}

    /**
     * @param  bool  $forHost  aperçu de l'hôte : ni publication, ni préavis, ni horizon, ni disponibilité
     * @return list<array{code: string, message: string}>
     */
    public function violations(Apartment $apartment, Stay $stay, bool $forHost = false): array
    {
        $violations = [];
        $add = function (string $code, array $replace = []) use (&$violations): void {
            $violations[] = ['code' => $code, 'message' => __("stay.$code", $replace)];
        };

        $today = CarbonImmutable::instance(Date::today());
        $periods = $this->periods->covering($apartment->id, $stay->arrival->toDateString(), $stay->departure->subDay()->toDateString());
        $arrivalPeriod = $periods->first(fn (RatePeriod $p) => $stay->arrival->betweenIncluded($p->starts_on, $p->ends_on));

        if (! $forHost && $apartment->status !== ApartmentStatus::Published) {
            $add('not_published');
        }
        if ($stay->guests() > $apartment->capacity) {
            $add('too_many_guests', ['capacity' => $apartment->capacity]);
        }

        if (! $forHost) {
            $firstArrival = $today->addDays((int) $this->settings->get('booking.min_notice_days', 1));
            $lastArrival = $today->addMonths((int) $this->settings->get('booking.horizon_months', 12));
            if ($stay->arrival->lt($firstArrival)) {
                $add('too_soon', ['date' => $firstArrival->translatedFormat('j F Y')]);
            }
            if ($stay->arrival->gt($lastArrival)) {
                $add('too_far', ['date' => $lastArrival->translatedFormat('j F Y')]);
            }
        }

        $minStay = $arrivalPeriod->min_stay ?? $apartment->min_stay;
        if ($stay->nights() < $minStay) {
            $add('min_stay', ['nights' => $minStay]);
        }
        if ($stay->nights() > $apartment->max_stay) {
            $add('max_stay', ['nights' => $apartment->max_stay]);
        }

        $arrivalDays = $arrivalPeriod?->arrival_days;
        if ($arrivalDays && ! in_array($stay->arrival->dayOfWeekIso, $arrivalDays, true)) {
            $add('arrival_day', ['days' => implode(', ', array_map(
                fn (int $day) => $today->startOfWeek()->addDays($day - 1)->translatedFormat('l'),
                $arrivalDays,
            ))]);
        }

        $covered = collect($stay->nightDates())->every(
            fn (CarbonImmutable $night) => $periods->contains(fn (RatePeriod $p) => $night->betweenIncluded($p->starts_on, $p->ends_on)),
        );
        if (! $covered) {
            $add('no_rate');
        }

        if (! $forHost && ! $this->isFree($apartment, $stay)) {
            $add('unavailable');
        }

        return $violations;
    }

    /**
     * Aucune nuit du séjour, ni le battement de ménage avant et après, ne touche un bloc actif.
     */
    public function isFree(Apartment $apartment, Stay $stay): bool
    {
        $turnover = $apartment->turnover_days;

        return ! $this->availability->hasActiveBlock(
            $apartment->id,
            $stay->arrival->subDays($turnover)->toDateString(),
            $stay->departure->addDays($turnover)->toDateString(),
        );
    }
}
