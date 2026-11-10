<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'key' => $word,
            'label' => ['fr' => ucfirst($word), 'en' => ucfirst($word)],
            'icon' => null,
        ];
    }
}
