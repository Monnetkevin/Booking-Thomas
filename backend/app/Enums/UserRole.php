<?php

namespace App\Enums;

/**
 * Rôles du back-office : le propriétaire des logements (admin) gère tout ; le ménage voit
 * son planning. Le rôle Gestionnaire a été retiré (décision du 2026-11-22), il pourra revenir.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Housekeeping = 'housekeeping';

    /**
     * Rôles qu'un administrateur peut attribuer à une invitation ou un changement de rôle.
     *
     * @return list<string>
     */
    public static function assignable(): array
    {
        return [self::Housekeeping->value];
    }
}
