<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utilisation d'un code par une réservation, avec la réduction et le code figés.
 *
 * @property int $coupon_id
 * @property int $booking_id
 * @property string $email
 * @property string $code
 * @property int $discount_amount
 * @property CarbonImmutable|null $released_at
 */
#[Fillable(['coupon_id', 'booking_id', 'email', 'code', 'discount_amount', 'released_at'])]
class CouponRedemption extends Model
{
    protected function casts(): array
    {
        return ['released_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
