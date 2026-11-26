<?php

namespace App\Exceptions\Review;

use App\Exceptions\DomainException;

/** Lien d'avis inconnu, expiré ou déjà utilisé : une seule erreur, sans dire laquelle. */
class ReviewInvitationInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('reviews.errors.invalid_link'));
    }

    protected function status(): int
    {
        return 410;
    }

    public function errorCode(): string
    {
        return 'review_link_invalid';
    }
}
