<?php

namespace App\Enums;

/** Période des statistiques : 6 derniers mois, 12 derniers mois, ou depuis le premier séjour. */
enum StatisticsPeriod: string
{
    case SixMonths = 'six_months';
    case Year = 'year';
    case All = 'all';

    /** Nombre de mois (mois en cours compris) ; null : depuis le premier séjour. */
    public function months(): ?int
    {
        return match ($this) {
            self::SixMonths => 6,
            self::Year => 12,
            self::All => null,
        };
    }
}
