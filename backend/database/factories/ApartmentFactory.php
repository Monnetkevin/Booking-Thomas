<?php

namespace Database\Factories;

use App\Enums\ApartmentStatus;
use App\Enums\CancellationPolicy;
use App\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Le '.fake()->unique()->word();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => ['fr' => $name, 'en' => $name],
            'description' => ['fr' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'address' => fake()->streetAddress(),
            'postal_code' => '72000',
            'city' => 'Le Mans',
            'latitude' => 48.0061,
            'longitude' => 0.1996,
            'capacity' => 4,
            'bedrooms' => 2,
            'beds' => 2,
            'bathrooms' => 1,
            'surface_m2' => 45,
            'min_stay' => 2,
            'max_stay' => 28,
            'turnover_days' => 0,
            'cleaning_fee' => 4000,
            'cancellation_policy' => CancellationPolicy::Moderate,
            'status' => ApartmentStatus::Published,
            'position' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ApartmentStatus::Draft]);
    }
}
