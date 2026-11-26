<?php

namespace App\Exceptions\Review;

use App\Exceptions\DomainException;

/** Le voyageur a déjà déposé son avis : plus d'invitation à renvoyer. */
class ReviewAlreadySubmittedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('reviews.errors.already_submitted', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'review_already_submitted';
    }
}
