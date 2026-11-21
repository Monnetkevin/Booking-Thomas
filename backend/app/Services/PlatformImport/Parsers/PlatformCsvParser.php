<?php

namespace App\Services\PlatformImport\Parsers;

use App\Services\PlatformImport\ParseResult;
use Carbon\CarbonImmutable;
use Closure;

/** Lecture de l'export CSV d'une plateforme ; une implémentation par plateforme. */
interface PlatformCsvParser
{
    /**
     * $knownStays donne les séjours déjà importés (codes de confirmation → arrivée, nuits,
     * annonce), pour les lignes qui ne portent pas les dates de leur séjour (un ajustement
     * Airbnb arrivé dans un export ultérieur).
     *
     * @param  (Closure(list<string>): array<string, array{start: CarbonImmutable, nights: int, listing: string}>)|null  $knownStays
     *
     * @throws InvalidPlatformFileException colonnes attendues absentes
     */
    public function parse(string $contents, ?Closure $knownStays = null): ParseResult;
}
