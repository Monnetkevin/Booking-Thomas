<?php

namespace App\Services\PlatformImport\Parsers;

use App\Services\PlatformImport\ParseResult;
use App\Services\PlatformImport\PlatformRow;

/**
 * Export des réservations de l'extranet Booking.com (CSV, ou Excel enregistré en CSV).
 * Les réservations annulées sont ignorées ; le net est le prix moins la commission. Sans
 * colonne de logement (un seul établissement), l'annonce s'appelle « Booking.com ».
 * À vérifier sur un vrai export du client : les noms de colonnes peuvent varier.
 */
class BookingCsvParser implements PlatformCsvParser
{
    private const COLUMNS = [
        'code' => ['Numéro de réservation', 'Book number', 'Reservation number'],
        'arrival' => ['Arrivée', 'Check-in'],
        'departure' => ['Départ', 'Check-out'],
        'status' => ['Statut', 'Status'],
        'price' => ['Prix', 'Price', 'Tarif'],
        'commission' => ['Montant de la commission', 'Commission amount', 'Commission'],
        'listing' => ['Type d\'hébergement', 'Unit type', 'Établissement', 'Property name'],
        'guest' => ['Nom du/des client(s)', 'Guest name(s)', 'Réservé par', 'Booked by'],
    ];

    private const REQUIRED = ['code', 'arrival', 'departure', 'price'];

    public function parse(string $contents): ParseResult
    {
        $table = new CsvTable($contents);
        $columns = array_map(fn (array $names) => $table->column($names), self::COLUMNS);
        $missing = array_values(array_filter(self::REQUIRED, fn (string $key) => $columns[$key] === null));
        if ($missing !== []) {
            throw new InvalidPlatformFileException(array_map(fn (string $key) => self::COLUMNS[$key][0], $missing));
        }

        $rows = [];
        $skipped = [];
        foreach ($table->rows() as ['line' => $line, 'cells' => $cells]) {
            $cell = fn (string $key): ?string => $columns[$key] === null ? null : ($cells[$columns[$key]] ?? null);
            $status = mb_strtolower((string) $cell('status'));
            if (str_contains($status, 'cancel') || str_contains($status, 'annul')) {
                continue;
            }
            $arrival = CsvTable::date($cell('arrival'), true);
            $departure = CsvTable::date($cell('departure'), true);
            $price = CsvTable::cents($cell('price'));
            $code = (string) $cell('code');
            if ($arrival === null || $departure === null || ! $departure->gt($arrival) || $price === null || $code === '') {
                $skipped[] = ['line' => $line, 'reason' => 'unreadable'];

                continue;
            }
            $commission = CsvTable::cents($cell('commission'));
            $rows[] = new PlatformRow(
                line: $line,
                confirmationCode: $code,
                listing: (string) ($cell('listing') ?: 'Booking.com'),
                guest: $cell('guest') ?: null,
                arrival: $arrival,
                departure: $departure,
                grossAmount: $price,
                fees: $commission,
                netAmount: $price - ($commission ?? 0),
            );
        }

        return new ParseResult($rows, $skipped);
    }
}
