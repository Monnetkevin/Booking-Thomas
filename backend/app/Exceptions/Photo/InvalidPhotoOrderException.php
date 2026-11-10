<?php

namespace App\Exceptions\Photo;

use App\Exceptions\DomainException;

class InvalidPhotoOrderException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('apartments.errors.invalid_photo_order'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'invalid_photo_order';
    }
}
