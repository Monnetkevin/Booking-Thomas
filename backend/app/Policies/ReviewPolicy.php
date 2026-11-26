<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/** Avis des voyageurs (noms, séjours) : administrateur actif seulement. */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /** Publier, refuser, répondre, renvoyer l'invitation. */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isActiveAdmin();
    }
}
