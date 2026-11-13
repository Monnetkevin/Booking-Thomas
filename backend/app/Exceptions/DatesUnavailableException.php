<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Les nuits demandées viennent d'être prises (contrainte d'exclusion PostgreSQL).
 * Rendue en 409 avec un message compréhensible par le voyageur.
 */
class DatesUnavailableException extends RuntimeException
{
    /** SQLSTATE d'une violation de contrainte d'exclusion. */
    public const SQLSTATE = '23P01';

    /** Contrainte qui protège les nuits occupées (voir la migration des contraintes). */
    public const CONSTRAINT = 'availability_blocks_no_overlap';

    public static function isExclusionViolation(Throwable $e): bool
    {
        return $e instanceof QueryException
            && $e->getCode() === self::SQLSTATE
            && str_contains($e->getMessage(), self::CONSTRAINT);
    }

    public static function from(QueryException $e): self
    {
        return new self(__('booking.dates_unavailable'), 0, $e);
    }

    /** Nuits prises ou battement de ménage non respecté, constaté sous le verrou du logement. */
    public static function taken(): self
    {
        return new self(__('booking.dates_unavailable'));
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'dates_unavailable',
        ], 409);
    }
}
