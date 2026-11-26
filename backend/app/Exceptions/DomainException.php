<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Règle métier non respectée, levée par un Service. Rendue en JSON avec un code stable
 * que la SPA peut reconnaître ; le message est traduit dans la langue de la requête.
 */
abstract class DomainException extends RuntimeException
{
    /** Code HTTP de la réponse (409 conflit, 422 action impossible…). */
    abstract protected function status(): int;

    /** Identifiant stable de l'erreur, ex. « apartment_has_upcoming_stays ». */
    abstract public function errorCode(): string;

    /**
     * Détails utiles à l'interface (liste de manques, etc.).
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return [];
    }

    public function render(): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'details' => $this->details() ?: null,
        ]), $this->status());
    }
}
