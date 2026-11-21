<?php

namespace App\Exceptions\Users;

use App\Exceptions\DomainException;

/**
 * Désactivation refusée : son propre compte, ou le dernier administrateur actif (plus
 * personne ne pourrait gérer le site).
 */
class AccountLockoutException extends DomainException
{
    public function __construct(private readonly string $reason)
    {
        parent::__construct(__("users.errors.$reason", [], 'fr'));
    }

    public static function self(): self
    {
        return new self('cannot_deactivate_self');
    }

    public static function adminRole(): self
    {
        return new self('admin_role_fixed');
    }

    public static function lastAdmin(): self
    {
        return new self('last_admin');
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return $this->reason;
    }
}
