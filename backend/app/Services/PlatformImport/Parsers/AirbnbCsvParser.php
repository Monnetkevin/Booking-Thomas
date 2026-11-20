<?php

namespace App\Services\PlatformImport\Parsers;

use App\Services\PlatformImport\ParseResult;
use App\Services\PlatformImport\PlatformRow;

/**
 * Export Airbnb « Revenus › Historique des transactions » (CSV, en français ou en anglais).
 * Les lignes « Réservation » sont des séjours. Les ajustements et annulations (remboursement
 * au voyageur, frais d'annulation…) sont des versements du même séjour, souvent négatifs :
 * ils s'ajoutent à son revenu, une annulation remboursée le ramène donc à zéro. Sans dates,
 * ils reprennent celles de la réservation de même code du fichier. Les versements vers le
 * compte bancaire sont ignorés. Le montant net est celui versé à l'hôte, les frais ceux d'Airbnb.
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
        'date' => ['Date'],
    ];

    private const REQUIRED = ['type', 'code', 'start', 'nights', 'listing', 'amount'];

    private const BOOKING_TYPES = ['réservation', 'reservation'];

    /** Fragments des types d'ajustement rattachés à un séjour (« Ajustement de la résolution »…). */
    private const ADJUSTMENT_TYPES = ['ajustement', 'adjustment', 'annulation', 'cancellation', 'résolution', 'resolution'];

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
        $entries = [];
        $stays = [];
        foreach ($table->rows() as ['line' => $line, 'cells' => $cells]) {
            $cell = fn (string $key): ?string => $columns[$key] === null ? null : ($cells[$columns[$key]] ?? null);
            $type = mb_strtolower(trim((string) $cell('type')));
            $adjustment = $this->isAdjustment($type);
            if (! $adjustment && ! in_array($type, self::BOOKING_TYPES, true)) {
                continue;
            }
            $entries[] = compact('line', 'cell', 'type', 'adjustment');
            $start = CsvTable::date($cell('start'), $dayFirst);
            if (! $adjustment && $start !== null && (int) $cell('nights') >= 1) {
                $stays[(string) $cell('code')] ??= ['start' => $start, 'nights' => (int) $cell('nights'), 'listing' => (string) $cell('listing')];
            }
        }

        foreach ($entries as ['line' => $line, 'cell' => $cell, 'type' => $type, 'adjustment' => $adjustment]) {
            $code = (string) $cell('code');
            $start = CsvTable::date($cell('start'), $dayFirst);
            $nights = (int) $cell('nights');
            $listing = (string) $cell('listing');
            // Un ajustement sans dates (ou sans annonce) reprend celles de la réservation du même code.
            $stay = $adjustment ? ($stays[$code] ?? null) : null;
            if ($stay !== null && ($start === null || $nights < 1)) {
                [$start, $nights] = [$stay['start'], $stay['nights']];
            }
            if ($stay !== null && $listing === '') {
                $listing = $stay['listing'];
            }
            $amount = CsvTable::cents($cell('amount'));
            if ($start === null || $nights < 1 || $amount === null || ! PlatformRow::fits($code, $listing)) {
                $skipped[] = ['line' => $line, 'reason' => 'unreadable'];

                continue;
            }
            $fee = CsvTable::cents($cell('fee'));
            $rows[] = new PlatformRow(
                line: $line,
                confirmationCode: $code,
                listing: $listing,
                guest: $cell('guest') ?: null,
                arrival: $start,
                departure: $start->addDays($nights),
                grossAmount: CsvTable::cents($cell('gross')),
                fees: $fee === null ? null : abs($fee),
                netAmount: $amount,
                // Un versement : code, date du versement, période et montant (un long séjour en a
                // plusieurs) ; le type distingue un ajustement d'une réservation de même montant.
                entryKey: $adjustment
                    ? PlatformRow::key($code, $type, (string) $cell('date'), $start->toDateString(), (string) $nights, (string) $amount)
                    : PlatformRow::key($code, (string) $cell('date'), $start->toDateString(), (string) $nights, (string) $amount),
            );
        }

        return (new ParseResult($rows, $skipped))->withinRange();
    }

    private function isAdjustment(string $type): bool
    {
        foreach (self::ADJUSTMENT_TYPES as $fragment) {
            if (str_contains($type, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
