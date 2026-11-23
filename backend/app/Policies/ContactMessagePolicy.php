<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/** Messages des visiteurs (coordonnées personnelles) : administrateur actif seulement. */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->isActiveAdmin();
    }
}
