<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $arrival = now()->addDays(fake()->numberBetween(10, 60))->startOfDay();
        $nights = 3;

        return [
            'reference' => 'BT-'.Str::upper(Str::random(8)),
            'apartment_id' => Apartment::factory(),
            'source' => BookingSource::Direct,
            'status' => BookingStatus::Confirmed,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => fake()->phoneNumber(),
            'guest_country' => 'FR',
            'locale' => 'fr',
            'adults' => 2,
            'children' => 0,
            'arrival' => $arrival->toDateString(),
            'departure' => $arrival->copy()->addDays($nights)->toDateString(),
            'accommodation_amount' => 24000,
            'cleaning_fee' => 4000,
            'tourist_tax' => 600,
            'total_amount' => 28600,
            'price_breakdown' => [],
            'cancellation_policy' => ['model' => 'moderate', 'tiers' => []],
            'confirmed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Pending,
            'confirmed_at' => null,
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
