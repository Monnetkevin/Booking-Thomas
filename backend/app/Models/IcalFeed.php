<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'apartment_id', 'provider', 'import_url', 'export_token', 'etag', 'content_hash',
    'last_synced_at', 'last_status', 'last_error', 'consecutive_failures', 'is_active',
])]
#[Hidden(['import_url', 'export_token'])]
class IcalFeed extends Model
{
    protected static function booted(): void
    {
        static::creating(function (IcalFeed $feed): void {
            $feed->export_token ??= Str::random(40);
        });
    }

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
