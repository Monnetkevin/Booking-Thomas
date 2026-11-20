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
}
