<?php

namespace App\Services\PlatformImport;

/** Lignes reconnues et lignes écartées (numéro de ligne et raison) d'un fichier. */
final readonly class ParseResult
{
    /**
     * @param  list<PlatformRow>  $rows
     * @param  list<array{line: int, reason: string}>  $skipped
     */
    public function __construct(
        public array $rows,
        public array $skipped,
    ) {}

    /**
     * Une réservation par code de confirmation : un long séjour payé en plusieurs versements
     * (plusieurs lignes au même code) devient une seule ligne, montants additionnés, de la
     * première arrivée au dernier départ.
     */
    public function mergedByCode(): self
    {
        /** @var array<string, PlatformRow> $merged */
        $merged = [];
        foreach ($this->rows as $row) {
            $previous = $merged[$row->confirmationCode] ?? null;
            $merged[$row->confirmationCode] = $previous === null ? $row : new PlatformRow(
                line: $previous->line,
                confirmationCode: $row->confirmationCode,
                listing: $previous->listing,
                guest: $previous->guest ?? $row->guest,
                arrival: $previous->arrival->min($row->arrival),
                departure: $previous->departure->max($row->departure),
                grossAmount: self::sum($previous->grossAmount, $row->grossAmount),
                fees: self::sum($previous->fees, $row->fees),
                netAmount: $previous->netAmount + $row->netAmount,
            );
        }

        return new self(array_values($merged), $this->skipped);
    }

    private static function sum(?int $a, ?int $b): ?int
    {
        return $a === null && $b === null ? null : (int) $a + (int) $b;
    }
}
