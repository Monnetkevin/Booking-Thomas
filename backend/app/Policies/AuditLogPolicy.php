<?php

namespace App\Policies;

use App\Models\User;

/** Journal d'audit : lu par un administrateur actif, jamais modifié depuis l'API. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }
}
