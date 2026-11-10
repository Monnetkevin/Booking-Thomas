<?php

namespace App\Exceptions\Photo;

use App\Exceptions\DomainException;

class TooManyPhotosException extends DomainException
{
    public function __construct(int $max)
    {
        parent::__construct(__('apartments.errors.too_many_photos', ['max' => $max]));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'too_many_photos';
    }
}
