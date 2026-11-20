<?php

namespace App\Services\PlatformImport\Parsers;

use App\Exceptions\DomainException;

/** Fichier de plus de MAX_ROWS lignes : refusé avant toute requête en base. */
class PlatformFileTooLargeException extends DomainException
{
    public function __construct(private readonly int $max)
    {
        parent::__construct(__('platform_import.too_large', ['max' => $max], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'platform_file_too_large';
    }

    public function details(): array
    {
        return ['max' => $this->max];
    }
}
