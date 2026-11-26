<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/** Période des chiffres du tableau de bord : semaine (lundi → dimanche), mois ou année civils. */
enum DashboardPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    /**
     * Période en cours qui contient ce jour : [début, fin[.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function current(CarbonImmutable $today): array
    {
        return match ($this) {
            self::Week => [$today->startOfWeek(CarbonImmutable::MONDAY), $today->startOfWeek(CarbonImmutable::MONDAY)->addWeek()],
            self::Month => [$today->startOfMonth(), $today->startOfMonth()->addMonth()],
            self::Year => [$today->startOfYear(), $today->startOfYear()->addYear()],
        };
    }

    /**
     * Période précédente de même nature, pour la comparaison.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previous(CarbonImmutable $today): array
    {
        [$from] = $this->current($today);

        return $this->current(match ($this) {
            self::Week => $from->subWeek(),
            self::Month => $from->subMonth(),
            self::Year => $from->subYear(),
        });
    }
}
