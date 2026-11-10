<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\IcalFeed;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IcalFeed>
 */
class IcalFeedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'apartment_id' => Apartment::factory(),
            'provider' => 'airbnb',
            'import_url' => 'https://www.airbnb.fr/calendar/ical/'.fake()->numerify('########').'.ics?s='.Str::random(32),
            'export_token' => Str::random(40),
            'is_active' => true,
        ];
    }
}
