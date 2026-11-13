<?php

namespace App\Exceptions\Ical;

use RuntimeException;
use Throwable;

/**
 * Échec d'une synchronisation (flux injoignable, fichier invalide, flux vide suspect).
 * Jamais renvoyée telle quelle : le message est enregistré sur le flux et les nuits déjà
 * connues sont conservées.
 */
class IcalSyncException extends RuntimeException
{
    public static function unreachable(?Throwable $previous = null): self
    {
        return new self(__('ical.errors.unreachable', [], 'fr'), 0, $previous);
    }

    public static function httpError(int $status): self
    {
        return new self(__('ical.errors.http', ['status' => $status], 'fr'));
    }

    public static function tooLarge(): self
    {
        return new self(__('ical.errors.too_large', [], 'fr'));
    }

    public static function invalid(?Throwable $previous = null): self
    {
        return new self(__('ical.errors.invalid', [], 'fr'), 0, $previous);
    }

    public static function suspiciouslyEmpty(): self
    {
        return new self(__('ical.errors.suspicious_empty', [], 'fr'));
    }
}
