<?php

namespace App\Exceptions\Coupon;

use App\Exceptions\DomainException;

/** Code inconnu, inactif, expiré, épuisé ou hors conditions : une seule erreur, volontairement vague. */
class CouponInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('coupons.invalid'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'coupon_invalid';
    }
}
