<?php

namespace App\Support;

use NumberFormatter;

/** Montants en centimes affichés dans la langue du lecteur : « 250,00 € », « €250.00 ». */
final class Money
{
    public static function format(int $cents, string $locale = 'fr'): string
    {
        $formatter = new NumberFormatter($locale === 'en' ? 'en_GB' : 'fr_FR', NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency($cents / 100, 'EUR');
    }
}
