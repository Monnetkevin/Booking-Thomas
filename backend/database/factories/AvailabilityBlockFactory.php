<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityBlock>
 */
class AvailabilityBlockFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(20)->startOfDay();

        return [
            'apartment_id' => Apartment::factory(),
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addDays(3)->toDateString(),
            'type' => BlockType::Manual,
            'is_active' => true,
            'has_conflict' => false,
        ];
    }

    /** Plage [from, to[ (to = jour de départ, libre). */
    public function between(string $from, string $to): static
    {
        return $this->state(fn (array $attributes) => ['starts_on' => $from, 'ends_on' => $to]);
    }
}
