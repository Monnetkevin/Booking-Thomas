<?php

namespace App\Models;

use Database\Factories\IcalFeedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Flux iCal d'un logement. Le token d'export (40 caractères aléatoires) est fourni à la
 * création par le service de synchronisation.
 */
#[Fillable([
    'apartment_id', 'provider', 'import_url', 'export_token', 'etag', 'content_hash',
    'last_synced_at', 'last_status', 'last_error', 'consecutive_failures', 'is_active',
])]
#[Hidden(['import_url', 'export_token'])]
class IcalFeed extends Model
{
    /** @use HasFactory<IcalFeedFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'import_url' => 'encrypted',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /** @return HasMany<AvailabilityBlock, $this> */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }
}
