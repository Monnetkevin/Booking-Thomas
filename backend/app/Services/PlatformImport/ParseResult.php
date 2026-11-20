<?php

namespace App\Services\PlatformImport;

/** Versements reconnus et lignes écartées (numéro de ligne et raison) d'un fichier. */
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
     * Lignes hors des bornes de la base (nuits, montants) écartées, comme les illisibles.
     */
    public function withinRange(): self
    {
        $rows = [];
        $skipped = $this->skipped;
        foreach ($this->rows as $row) {
            if ($row->inRange()) {
                $rows[] = $row;
            } else {
                $skipped[] = ['line' => $row->line, 'reason' => 'out_of_range'];
            }
        }
        usort($skipped, fn (array $a, array $b) => $a['line'] <=> $b['line']);

        return new self($rows, $skipped);
    }
}
