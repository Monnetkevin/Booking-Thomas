<?php

namespace App\Exceptions\Users;

use App\Exceptions\DomainException;

/**
 * Action refusée sur un compte : désactiver son propre compte ou le dernier administrateur
 * actif (plus personne ne pourrait gérer le site), changer le rôle d'un administrateur,
 * envoyer un lien de mot de passe à un compte désactivé.
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

    public static function inactive(): self
    {
        return new self('inactive_account');
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
