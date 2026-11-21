<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/** Messages des visiteurs (coordonnées personnelles) : administrateur actif seulement. */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canOperate();
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->canOperate();
    }
}
