<?php

namespace App\Services\PlatformImport\Parsers;

use App\Exceptions\DomainException;

/** Fichier qui n'est pas l'export attendu de la plateforme (colonnes manquantes). */
class InvalidPlatformFileException extends DomainException
{
    /**
     * @param  list<string>  $missing
     */
    public function __construct(private readonly array $missing)
    {
        parent::__construct(__('platform_import.invalid_file', ['columns' => implode(', ', $missing)], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'invalid_platform_file';
    }

    public function details(): array
    {
        return ['missing' => $this->missing];
    }
}
