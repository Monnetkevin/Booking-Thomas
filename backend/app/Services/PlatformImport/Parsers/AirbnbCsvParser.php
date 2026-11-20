<?php

namespace App\Services\PlatformImport\Parsers;

use App\Services\PlatformImport\ParseResult;
use App\Services\PlatformImport\PlatformRow;

/**
 * Export Airbnb « Revenus › Historique des transactions » (CSV, en français ou en anglais).
 * Seules les lignes de type « Réservation » sont des séjours ; les versements et ajustements
 * sont ignorés. Le montant net est celui versé à l'hôte, les frais sont ceux d'Airbnb.
 * À vérifier sur un vrai export du client : les noms de colonnes peuvent varier.
 */
class AirbnbCsvParser implements PlatformCsvParser
{
    private const COLUMNS = [
        'type' => ['Type'],
        'code' => ['Code de confirmation', 'Confirmation Code', 'Confirmation code'],
        'start' => ['Date de début', 'Start Date', 'Start date'],
        'nights' => ['Nuits', 'Nights'],
        'guest' => ['Voyageur', 'Guest'],
        'listing' => ['Logement', 'Annonce', 'Listing'],
        'amount' => ['Montant', 'Amount'],
        'fee' => ['Frais de service', 'Service fee'],
        'gross' => ['Revenus bruts', 'Gross earnings'],
    ];

    private const REQUIRED = ['type', 'code', 'start', 'nights', 'listing', 'amount'];

    private const BOOKING_TYPES = ['réservation', 'reservation'];

    public function parse(string $contents): ParseResult
    {
        $table = new CsvTable($contents);
        $columns = array_map(fn (array $names) => $table->column($names), self::COLUMNS);
        $missing = array_values(array_filter(self::REQUIRED, fn (string $key) => $columns[$key] === null));
        if ($missing !== []) {
            throw new InvalidPlatformFileException(array_map(fn (string $key) => self::COLUMNS[$key][0], $missing));
        }
        // Export français : dates JJ/MM/AAAA ; anglais : MM/JJ/AAAA.
        $dayFirst = $table->column(['Code de confirmation']) !== null;

        $rows = [];
        $skipped = [];
        foreach ($table->rows() as ['line' => $line, 'cells' => $cells]) {
            $cell = fn (string $key): ?string => $columns[$key] === null ? null : ($cells[$columns[$key]] ?? null);
            if (! in_array(mb_strtolower((string) $cell('type')), self::BOOKING_TYPES, true)) {
                continue;
            }
            $start = CsvTable::date($cell('start'), $dayFirst);
            $nights = (int) $cell('nights');
            $amount = CsvTable::cents($cell('amount'));
            $code = (string) $cell('code');
            if ($start === null || $nights < 1 || $amount === null || $code === '') {
                $skipped[] = ['line' => $line, 'reason' => 'unreadable'];

                continue;
            }
            $fee = CsvTable::cents($cell('fee'));
            $rows[] = new PlatformRow(
                line: $line,
                confirmationCode: $code,
                listing: (string) $cell('listing'),
                guest: $cell('guest') ?: null,
                arrival: $start,
                departure: $start->addDays($nights),
                grossAmount: CsvTable::cents($cell('gross')),
                fees: $fee === null ? null : abs($fee),
                netAmount: $amount,
            );
        }

        return new ParseResult($rows, $skipped);
    }
}
