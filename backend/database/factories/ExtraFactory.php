<?php

namespace Database\Factories;

use App\Enums\ExtraPricing;
use App\Models\Extra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Extra>
 */
class ExtraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ['fr' => 'Bouteille de champagne', 'en' => 'Bottle of champagne'],
            'description' => ['fr' => 'Servie fraîche à votre arrivée.', 'en' => 'Served chilled on arrival.'],
            'price' => 4500,
            'pricing' => ExtraPricing::PerUnit,
            'max_quantity' => 3,
            'lead_time_hours' => 48,
            'is_active' => true,
        ];
    }
}
