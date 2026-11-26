<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\RatePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RatePeriod>
 */
class RatePeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'apartment_id' => Apartment::factory(),
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'nightly_price' => 8000,
            'min_stay' => 2,
            'weekend_supplement' => null,
        ];
    }
}
