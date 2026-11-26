<?php

namespace App\Models;

use App\Enums\ExtraPricing;
use Database\Factories\ExtraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Option proposée par l'hôte (champagne, panier repas, lit bébé), payante ou gratuite.
 *
 * @property int $id
 * @property int $price
 * @property ExtraPricing $pricing
 * @property int $max_quantity
 * @property int $lead_time_hours
 * @property bool $is_active
 * @property int $position
 */
#[Fillable(['name', 'description', 'price', 'pricing', 'max_quantity', 'lead_time_hours', 'is_active', 'position'])]
class Extra extends Model
{
    /** @use HasFactory<ExtraFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'pricing' => ExtraPricing::class,
            'price' => 'integer',
            'max_quantity' => 'integer',
            'lead_time_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Apartment, $this> */
    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class);
    }
}
