<?php

namespace App\Services\PlatformImport\Parsers;

use App\Services\PlatformImport\ParseResult;

/** Lecture de l'export CSV d'une plateforme ; une implémentation par plateforme. */
interface PlatformCsvParser
{
    /**
     * @throws InvalidPlatformFileException colonnes attendues absentes
     */
    public function parse(string $contents): ParseResult;
}
