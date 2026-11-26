<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

/** Codes promo et offres (prix) : administrateur actif seulement. */
class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->isActiveAdmin();
    }
}
