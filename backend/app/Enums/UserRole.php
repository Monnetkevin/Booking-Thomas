<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    /** Gestionnaire : tout le back-office sauf réglages, utilisateurs, tarifs et coupons. */
    case Manager = 'manager';
    case Housekeeping = 'housekeeping';

    /**
     * Profil de notifications : le gestionnaire reçoit les mêmes alertes que l'administrateur.
     */
    public function notificationProfile(): self
    {
        return $this === self::Manager ? self::Admin : $this;
    }

    /**
     * Rôles qui reçoivent les notifications destinées à ce profil.
     *
     * @return list<self>
     */
    public function members(): array
    {
        return $this === self::Admin ? [self::Admin, self::Manager] : [$this];
    }

    /**
     * Rôles qu'un administrateur peut attribuer à une invitation ou un changement de rôle.
     *
     * @return list<string>
     */
    public static function assignable(): array
    {
        return [self::Manager->value, self::Housekeeping->value];
    }
}
