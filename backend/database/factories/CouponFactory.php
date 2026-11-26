<?php

namespace Database\Factories;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'target' => CouponTarget::Stay,
            'type' => CouponType::Percent,
            'value' => 10,
            'free_quantity' => 1,
            'automatic' => false,
            'is_active' => true,
        ];
    }
}
