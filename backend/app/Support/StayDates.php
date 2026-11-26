<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Dates d'un séjour telles qu'affichées dans les e-mails et le PDF : « ven. 18 déc. 2026 ». */
final class StayDates
{
    public static function day(CarbonInterface $date, string $locale): string
    {
        return $date->locale($locale)->translatedFormat('D j M Y');
    }

    /** « ven. 18 déc. 2026 → lun. 21 déc. 2026 » (arrivée → départ). */
    public static function range(CarbonInterface $arrival, CarbonInterface $departure, string $locale): string
    {
        return self::day($arrival, $locale).' → '.self::day($departure, $locale);
    }
}
