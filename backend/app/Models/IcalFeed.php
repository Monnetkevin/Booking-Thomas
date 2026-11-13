<?php

namespace App\Models;

use App\Enums\IcalProvider;
use App\Enums\IcalSyncStatus;
use Carbon\Carbon;
use Database\Factories\IcalFeedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Flux iCal d'un logement chez une plateforme : URL d'import (chiffrée) du calendrier de
 * la plateforme, et token d'export (40 caractères aléatoires) de l'URL que la plateforme
 * lit sur le site.
 *
 * @property int $apartment_id
 * @property IcalProvider $provider
 * @property string|null $import_url
 * @property string $export_token
 * @property string|null $etag
 * @property string|null $content_hash
 * @property Carbon|null $last_synced_at
 * @property IcalSyncStatus|null $last_status
 * @property string|null $last_error
 * @property int $consecutive_failures
 * @property bool $is_active
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
            'provider' => IcalProvider::class,
            'import_url' => 'encrypted',
            'last_status' => IcalSyncStatus::class,
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
