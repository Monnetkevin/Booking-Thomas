<?php

namespace App\Models;

use Database\Factories\RatePeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Période tarifaire inclusive : chaque nuit du starts_on au ends_on compris prend ce prix.
 */
#[Fillable(['apartment_id', 'starts_on', 'ends_on', 'nightly_price', 'min_stay', 'weekend_supplement', 'arrival_days'])]
class RatePeriod extends Model
{
    /** @use HasFactory<RatePeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'arrival_days' => 'array',
        ];
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
