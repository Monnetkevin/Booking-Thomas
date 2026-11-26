<?php

namespace App\Exceptions\Review;

use App\Exceptions\DomainException;

/** Une invitation sans réponse ne se publie, ne se refuse ni ne reçoit de réponse. */
class ReviewNotSubmittedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('reviews.errors.not_submitted', [], 'fr'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'review_not_submitted';
    }
}
