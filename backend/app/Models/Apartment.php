<?php

namespace App\Models;

use App\Enums\ApartmentStatus;
use App\Enums\CancellationPolicy;
use Database\Factories\ApartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'slug', 'name', 'description', 'address', 'postal_code', 'city', 'latitude', 'longitude',
    'capacity', 'bedrooms', 'beds', 'bathrooms', 'surface_m2', 'check_in_time', 'check_out_time',
    'min_stay', 'max_stay', 'turnover_days', 'cleaning_fee', 'cancellation_policy',
    'registration_number', 'status', 'position',
])]
class Apartment extends Model implements HasMedia
{
    /** @use HasFactory<ApartmentFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => ApartmentStatus::class,
            'cancellation_policy' => CancellationPolicy::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ApartmentStatus::Published);
    }

    /** @return BelongsToMany<Amenity, $this> */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    /** @return HasMany<RatePeriod, $this> */
    public function ratePeriods(): HasMany
    {
        return $this->hasMany(RatePeriod::class)->orderBy('starts_on');
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<AvailabilityBlock, $this> */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /** @return HasMany<IcalFeed, $this> */
    public function icalFeeds(): HasMany
    {
        return $this->hasMany(IcalFeed::class);
    }
}
